import re
import unicodedata
from difflib import SequenceMatcher
from typing import Any

from mati_ai.contracts.vision import CatalogValue
from mati_ai.identification.codes import valid_gtin


def clean_text(value: str) -> str:
    return "\n".join(
        re.sub(r"[^\S\n]+", " ", unicodedata.normalize("NFKC", line)).strip()
        for line in value.splitlines()
        if line.strip()
    )


def normalized(value: str | None) -> str:
    return (
        re.sub(
            r"\s+",
            " ",
            "".join(
                c
                for c in unicodedata.normalize("NFKD", value or "")
                if not unicodedata.combining(c)
            ),
        )
        .strip()
        .upper()
    )


def catalog_candidate(
    text: str, catalog: list[CatalogValue], *, size: bool = False
) -> dict | None:
    lines = [normalized(line) for line in text.splitlines()]
    tokens = set(re.findall(r"[A-Z0-9]+(?:[/.-][A-Z0-9]+)*", normalized(text)))
    best: dict[str, Any] | None = None
    for item in catalog:
        aliases = [normalized(item.name)] + [normalized(a) for a in item.aliases]
        scores = []
        for alias in aliases:
            if alias in lines or (size and alias in tokens):
                scores.append(1.0)
            elif not size:
                scores.extend(
                    1.0
                    if re.search(r"(?<!\w)" + re.escape(alias) + r"(?!\w)", line)
                    else SequenceMatcher(None, alias, line).ratio()
                    for line in lines
                )
        score = max(scores, default=0)
        if score >= (0.99 if size else 0.82) and (
            best is None or score > best["confidence_score"]
        ):
            best = {
                "id": item.id,
                "name": item.name,
                "confidence_score": round(score, 3),
                "source": "ocr_catalog_match",
            }
    return best


MATERIALS = {
    "COTTON": "algodón",
    "ALGODON": "algodón",
    "POLYESTER": "poliéster",
    "POLIESTER": "poliéster",
    "ELASTANE": "elastano",
    "ELASTANO": "elastano",
    "SPANDEX": "elastano",
    "WOOL": "lana",
    "LANA": "lana",
    "LINEN": "lino",
    "LINO": "lino",
    "VISCOSE": "viscosa",
    "VISCOSA": "viscosa",
    "NYLON": "nylon",
    "SILK": "seda",
    "SEDA": "seda",
    "ACRYLIC": "acrílico",
    "ACRILICO": "acrílico",
}


def normalize_label(
    raw: str, brands: list[CatalogValue], sizes: list[CatalogValue]
) -> dict:
    text = clean_text(raw)
    upper = normalized(text)
    composition = []
    for percentage, material in re.findall(
        r"(\d{1,3}(?:[.,]\d+)?)\s*%\s*([A-ZÁÉÍÓÚÑ]+)", text.upper()
    ):
        number = float(percentage.replace(",", "."))
        if number <= 100:
            composition.append(
                {
                    "material": MATERIALS.get(normalized(material), material.lower()),
                    "percentage": number,
                    "source": "ocr_label",
                }
            )
    model = re.search(
        r"\b(?:ART(?:ICULO)?|MODEL(?:O)?|STYLE|REF(?:ERENCIA)?)\s*[:.#-]?\s*([A-Z0-9][A-Z0-9/._-]{2,40})",
        upper,
    )
    sku = re.search(r"\bSKU\s*[:.#-]?\s*([A-Z0-9][A-Z0-9/._-]{2,40})", upper)
    gtins = list(
        dict.fromkeys(
            value
            for value in re.findall(
                r"(?<!\d)(?:\d{14}|\d{13}|\d{12}|\d{8})(?!\d)", upper
            )
            if valid_gtin(value)
        )
    )
    warnings = []
    if composition and abs(sum(item["percentage"] for item in composition) - 100) > 1:
        warnings.append("COMPOSITION_TOTAL_REQUIRES_REVIEW")
    return {
        "raw_text": raw,
        "normalized_text": text,
        "brand_candidate": catalog_candidate(text, brands),
        "size_candidate": catalog_candidate(text, sizes, size=True),
        "model_code_candidate": model.group(1) if model else None,
        "sku_candidate": sku.group(1) if sku else None,
        "gtin_candidates": gtins,
        "composition": composition,
        "warnings": warnings,
    }
