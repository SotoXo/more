import re
import unicodedata
from difflib import SequenceMatcher

from mati_ai.vision.embeddings import cosine_similarity

from .contracts import ExternalProduct, ExternalQuery


def normalize(value: str | None) -> str:
    value = unicodedata.normalize("NFKD", value or "")
    return (
        re.sub(r"\s+", " ", "".join(c for c in value if not unicodedata.combining(c)))
        .strip()
        .casefold()
    )


def normalized_gtin(value: str | None) -> str | None:
    digits = re.sub(r"[\s-]", "", value or "")
    if (
        not digits.isascii()
        or not digits.isdigit()
        or len(digits) not in (8, 12, 13, 14)
    ):
        return None
    check = (
        10
        - sum(
            int(d) * (3 if i % 2 == 0 else 1)
            for i, d in enumerate(reversed(digits[:-1]))
        )
        % 10
    ) % 10
    return digits.zfill(14) if check == int(digits[-1]) else None


def rank_products(
    query: ExternalQuery, candidates: list[ExternalProduct]
) -> list[ExternalProduct]:
    output = []
    seen = set()
    for product in candidates:
        key = (product.source_id, product.canonical_url)
        if key in seen or product.rights_status == "prohibited":
            continue
        seen.add(key)
        p = product.model_copy(deep=True)
        exact_gtin = bool(
            normalized_gtin(query.gtin)
            and normalized_gtin(query.gtin) == normalized_gtin(p.gtin)
        )
        exact_model = bool(
            query.model_code and normalize(query.model_code) == normalize(p.model_code)
        )
        exact_brand = bool(query.brand and normalize(query.brand) == normalize(p.brand))
        if query.gtin and p.gtin and not exact_gtin:
            p.conflicts.append("GTIN_CONFLICT")
        if query.brand and p.brand and not exact_brand:
            p.conflicts.append("BRAND_CONFLICT")
        if query.model_code and p.model_code and not exact_model:
            p.conflicts.append("MODEL_CONFLICT")
        text = normalize(query.query)
        target = normalize(
            " ".join(filter(None, [p.title, p.brand, p.model_code, p.description]))
        )
        p.text_score = (
            round(SequenceMatcher(None, text, target).ratio(), 5) if text else 0.0
        )
        if (
            query.embedding
            and p.embedding
            and query.model_id
            and query.model_revision
            and query.model_id == p.model_id
            and query.model_revision == p.model_revision
            and len(query.embedding) == len(p.embedding)
        ):
            similarity = cosine_similarity(query.embedding, p.embedding)
            if similarity is not None:
                p.visual_score = max(0.0, similarity)
        p.identifier_score = (
            1.0
            if exact_gtin
            else (0.9 if exact_model and exact_brand else 0.8 if exact_model else 0.0)
        )
        attributes = (
            float(
                bool(
                    query.category
                    and normalize(query.category) == normalize(p.category)
                )
            )
            * 0.5
        )
        attributes += (
            float(bool(query.color and normalize(query.color) == normalize(p.color)))
            * 0.5
        )
        # Evidence tiers ensure soft similarity cannot outrank an exact identifier.
        if exact_gtin:
            score = 0.95 + 0.04 * p.text_score
        elif exact_model:
            score = (0.85 if exact_brand else 0.78) + 0.04 * p.text_score
        else:
            score = (
                0.40 * p.text_score
                + 0.25 * (p.visual_score or 0)
                + 0.10 * exact_brand
                + 0.05 * attributes
            )
        if "GTIN_CONFLICT" in p.conflicts:
            score = 0.0
        if "BRAND_CONFLICT" in p.conflicts:
            score = min(score * 0.2, 0.15)
        if "MODEL_CONFLICT" in p.conflicts:
            score = min(score * 0.2, 0.15)
        p.final_score = round(score, 5)
        p.components = {
            "identifier": p.identifier_score,
            "text": p.text_score,
            "visual": p.visual_score or 0,
            "attributes": attributes,
        }
        p.warnings += ["CANDIDATE_REQUIRES_HUMAN_REVIEW"]
        if p.rights_status == "unknown":
            p.warnings.append("IMAGE_RIGHTS_UNKNOWN")
        output.append(p)
    return sorted(output, key=lambda x: x.final_score, reverse=True)[: query.limit]
