import cv2
import numpy as np
from PIL import Image


def assess_quality(image: Image.Image) -> dict:
    preview = image.copy()
    preview.thumbnail((1024, 1024))
    gray = cv2.cvtColor(np.asarray(preview), cv2.COLOR_RGB2GRAY)
    blur = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    low, high = np.percentile(gray, [5, 95])
    shadows, highlights = float(np.mean(gray < 10)), float(np.mean(gray > 245))
    warnings = []
    if blur < 50:
        warnings.append("POSSIBLE_BLUR_OR_UNIFORM_SURFACE")
    if shadows > 0.55:
        warnings.append("UNDEREXPOSED")
    if highlights > 0.65:
        warnings.append("OVEREXPOSED_OR_WHITE_BACKGROUND")
    if high - low < 30:
        warnings.append("LOW_CONTRAST")
    if min(image.size) < 300:
        warnings.append("LOW_RESOLUTION")
    return {
        "blur_score": round(blur, 3),
        "exposure": {"shadow_fraction": shadows, "highlight_fraction": highlights},
        "contrast": float(high - low),
        "width": image.width,
        "height": image.height,
        "status": "review" if warnings else "acceptable",
        "warnings": warnings,
    }
