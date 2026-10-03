from mati_ai.contracts.vision import CatalogValue, CategoryPrediction

# English aliases improve the English-trained baseline while preserving Laravel IDs and labels.
ALIASES = {
    "PANTALON": "trousers",
    "PANTALÓN": "trousers",
    "PANTALONES": "trousers",
    "FALDA": "skirt",
    "FALDAS": "skirt",
    "BLUSA": "blouse",
    "BLUSAS": "blouse",
    "POLERA": "t-shirt",
    "CAMISA": "shirt",
    "CAMISAS": "shirt",
    "VESTIDO": "dress",
    "VESTIDOS": "dress",
    "CHAQUETA": "jacket",
    "BLAZER": "blazer",
    "CONJUNTO": "matching clothing set",
    "SHORT": "shorts",
    "ABRIGO": "coat",
    "ABRIGOS": "coats",
    "BLAZERS": "blazers",
    "CONJUNTOS": "matching clothing sets",
    "ACCESORIOS": "fashion accessories",
    "POLERAS": "t-shirts",
    "CHAQUETAS": "jackets",
    "SWEATER": "sweater",
    "POLOS Y KNITWEAR": "polo shirt or knitwear",
}


def category_prompts(categories: list[CatalogValue]) -> list[str]:
    return [
        f"This is a photo of {entry.aliases[0] if entry.aliases else ALIASES.get(entry.name.upper(), entry.name.lower())}."
        for entry in categories
    ]


def classify(categories: list[CatalogValue], scores: list[float]) -> dict:
    values = [
        CategoryPrediction(
            category_id=item.id,
            category_name=item.name,
            similarity_score=round(score, 6),
        ).model_dump()
        for item, score in zip(categories, scores, strict=True)
    ]
    values.sort(key=lambda item: item["similarity_score"], reverse=True)
    margin = (
        values[0]["similarity_score"] - values[1]["similarity_score"]
        if len(values) > 1
        else None
    )
    return {
        "top_k": values[:3],
        "margin": round(margin, 6) if margin is not None else None,
        "abstained": not values
        or values[0]["similarity_score"] < 0.08
        or (margin is not None and margin < 0.005),
        "score_kind": "cosine_similarity_uncalibrated",
        "source": "zero_shot_visual_model",
    }
