import re
import unicodedata
from typing import Any

import cv2
import numpy as np
from PIL import Image

from mati_ai.contracts.vision import CatalogValue

FALLBACK_HEX = {
    "NEGRO": "000000",
    "BLANCO": "ffffff",
    "BEIGE": "d9c5a0",
    "AZUL": "2454a0",
    "ROJO": "c62828",
    "VERDE": "38804c",
    "AMARILLO": "f1d144",
    "GRIS": "808080",
    "ROSA": "ed9cb3",
    "MORADO": "803d99",
    "MARRON": "805532",
    "CAFE": "805532",
    "NARANJA": "e78534",
    "CELESTE": "85c5e5",
    "CREMA": "fff0ca",
}


def _rgb(value: CatalogValue):
    name = "".join(
        c
        for c in unicodedata.normalize("NFKD", value.name.upper())
        if not unicodedata.combining(c)
    )
    color = value.hex or FALLBACK_HEX.get(name)
    if not color or not re.fullmatch(r"#?[0-9a-fA-F]{6}", color):
        return None
    return [int(color.lstrip("#")[i : i + 2], 16) for i in (0, 2, 4)]


def extract_colors(image: Image.Image, catalog: list[CatalogValue]) -> dict:
    preview = image.copy()
    preview.thumbnail((160, 160))
    rgb = np.asarray(preview).astype(np.float32) / 255
    lab = cv2.cvtColor(rgb, cv2.COLOR_RGB2LAB)
    border = np.concatenate([lab[0], lab[-1], lab[:, 0], lab[:, -1]])
    background = np.median(border, axis=0)
    uniform = float(np.median(np.linalg.norm(border - background, axis=1))) < 7
    mask = np.linalg.norm(lab - background, axis=2) > 12
    fraction = float(mask.mean())
    foreground = uniform and 0.12 < fraction < 0.94
    pixels = lab[mask] if foreground else lab.reshape(-1, 3)
    # Deterministic Lab quantization avoids stochastic clustering and excessive thread overhead.
    bins = np.floor(pixels / np.array([10, 12, 12])).astype(int)
    _, inverse, counts = np.unique(
        bins, axis=0, return_inverse=True, return_counts=True
    )
    clusters = []
    for index in counts.argsort()[::-1][:5]:
        center = pixels[inverse == index].mean(axis=0)
        converted = cv2.cvtColor(
            center.reshape(1, 1, 3).astype(np.float32), cv2.COLOR_LAB2RGB
        )[0, 0]
        rgb_value = np.clip(np.rint(converted * 255), 0, 255).astype(int)
        clusters.append((center, rgb_value, float(counts[index] / len(pixels))))
    refs = [(entry, _rgb(entry)) for entry in catalog]
    refs = [
        (
            entry,
            cv2.cvtColor(
                np.array(color, np.float32).reshape(1, 1, 3) / 255, cv2.COLOR_RGB2LAB
            )[0, 0],
        )
        for entry, color in refs
        if color is not None
    ]
    palette: list[dict[str, Any]] = []
    for center, color, percentage in clusters:
        candidate, distance = None, None
        if refs:
            candidate, ref = min(
                refs, key=lambda pair: float(np.linalg.norm(pair[1] - center))
            )
            distance = float(np.linalg.norm(ref - center))
            if distance > 45:
                candidate = None
        palette.append(
            {
                "hex": "#" + "".join(f"{v:02x}" for v in color),
                "percentage": round(percentage, 4),
                "catalog_id": candidate.id if candidate else None,
                "catalog_name": candidate.name if candidate else None,
                "distance_delta_e": round(distance, 3)
                if distance is not None
                else None,
                "confidence_score": round(
                    max(0, 1 - (distance or 0) / 60) * (0.85 if foreground else 0.55), 3
                ),
                "source": "lab_quantization",
            }
        )
    return {
        "dominant_color": palette[0] if palette else None,
        "secondary_colors": palette[1:],
        "palette": palette,
        "foreground_confidence": 0.8 if foreground else 0.35,
        "warnings": [] if foreground else ["BACKGROUND_MAY_AFFECT_COLOR"],
        "coverage": round(sum(item["percentage"] for item in palette), 4),
    }
