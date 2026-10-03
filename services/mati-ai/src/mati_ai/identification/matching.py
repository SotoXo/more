from difflib import SequenceMatcher
from typing import Any

from mati_ai.contracts.identification import MatchItem
from mati_ai.identification.codes import valid_gtin
from mati_ai.identification.normalization import normalized
from mati_ai.vision.embeddings import cosine_similarity


def rank_matches(
    query: MatchItem, candidates: list[MatchItem], limit: int = 10
) -> list[dict]:
    ranked: list[dict[str, Any]] = []
    for candidate in candidates:
        components, conflicts, warnings = {}, [], []
        for key in ("sku", "gtin", "model_code", "brand", "category", "color"):
            left, right = getattr(query, key), getattr(candidate, key)
            if left is not None and right is not None:
                if key == "gtin" and valid_gtin(str(left)) and valid_gtin(str(right)):
                    left, right = str(left).zfill(14), str(right).zfill(14)
                components[key] = (
                    1.0 if normalized(str(left)) == normalized(str(right)) else 0.0
                )
                if key in ("gtin", "brand", "model_code") and not components[key]:
                    conflicts.append(key.upper() + "_CONFLICT")
        if "gtin" in components and not (
            valid_gtin(query.gtin or "") and valid_gtin(candidate.gtin or "")
        ):
            components["gtin"] = 0.0
            warnings.append("INVALID_GTIN_NOT_IDENTITY_EVIDENCE")
        if query.text and candidate.text:
            components["text"] = SequenceMatcher(
                None, normalized(query.text), normalized(candidate.text)
            ).ratio()
        if query.image_sha256 and candidate.image_sha256:
            components["exact_image"] = float(
                query.image_sha256 == candidate.image_sha256
            )
        if query.embedding and candidate.embedding:
            if (query.embedding.model_id, query.embedding.model_revision) == (
                candidate.embedding.model_id,
                candidate.embedding.model_revision,
            ):
                score = cosine_similarity(
                    query.embedding.values, candidate.embedding.values
                )
                if score is not None:
                    components["visual"] = max(0, score)
                else:
                    warnings.append("INVALID_EMBEDDING")
            else:
                warnings.append("EMBEDDING_MODEL_MISMATCH")
        if components.get("sku") == 1:
            score, match_type = 1.0, "exact_sku"
        elif components.get("gtin") == 1:
            score, match_type = 0.98, "exact_gtin"
        elif components.get("model_code") == 1:
            score, match_type = (
                (0.94 if components.get("brand") else 0.86),
                "model_candidate",
            )
        else:
            score = min(
                0.79,
                0.35 * components.get("text", 0)
                + 0.32 * components.get("visual", 0)
                + 0.45 * components.get("exact_image", 0)
                + 0.05 * components.get("brand", 0)
                + 0.04 * components.get("category", 0)
                + 0.03 * components.get("color", 0),
            )
            match_type = "similar_candidate"
        if "GTIN_CONFLICT" in conflicts:
            score = min(score, 0.05)
        if "MODEL_CODE_CONFLICT" in conflicts:
            score *= 0.4
        if "BRAND_CONFLICT" in conflicts:
            score *= 0.5
        if components.get("visual") is not None or components.get("exact_image"):
            warnings.append("VISUAL_SIMILARITY_DOES_NOT_PROVE_IDENTITY")
        ranked.append(
            {
                "candidate_id": candidate.candidate_id,
                "score": round(score, 6),
                "components": components,
                "conflicts": conflicts,
                "warnings": warnings,
                "match_type": match_type,
                "source": "internal_catalog_ranking",
            }
        )
    ranked.sort(key=lambda item: item["score"], reverse=True)
    return ranked[:limit]
