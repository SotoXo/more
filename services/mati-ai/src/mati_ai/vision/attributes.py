from mati_ai.contracts.vision import CatalogValue

PATTERN_ALIASES = {
    "LISO": "solid plain",
    "RAYAS": "striped",
    "CUADROS": "plaid checked",
    "FLORAL": "floral",
    "ANIMAL PRINT": "animal print",
    "ESTAMPADO": "printed graphic",
    "GRAFICO": "graphic",
}


def pattern_prompts(patterns: list[CatalogValue]) -> list[str]:
    return [
        f"This is a photo of clothing with a {PATTERN_ALIASES.get(item.name.upper(), item.name.lower())} pattern."
        for item in patterns
    ]


def pattern_prediction(
    patterns: list[CatalogValue], scores: list[float]
) -> dict | None:
    if not patterns:
        return None
    ranked = sorted(
        zip(patterns, scores, strict=True), key=lambda item: item[1], reverse=True
    )
    item, score = ranked[0]
    if score < 0.08 or (len(ranked) > 1 and score - ranked[1][1] < 0.005):
        return None
    return {
        "id": item.id,
        "name": item.name,
        "similarity_score": round(score, 6),
        "observable_from_image": True,
        "source": "zero_shot_visual_model",
        "warnings": ["UNCALIBRATED_VISUAL_SUGGESTION"],
    }
