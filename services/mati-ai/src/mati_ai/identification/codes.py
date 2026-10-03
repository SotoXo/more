import re
from typing import Any, cast
from urllib.parse import urlsplit

import cv2
import numpy as np
from PIL import Image


def valid_gtin(value: str) -> bool:
    if not re.fullmatch(r"(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})", value):
        return False
    total = sum(
        int(digit) * (3 if index % 2 == 0 else 1)
        for index, digit in enumerate(reversed(value[:-1]))
    )
    return (10 - total % 10) % 10 == int(value[-1])


def expand_upce(value: str) -> str | None:
    if not re.fullmatch(r"[01][0-9]{7}", value):
        return None
    number_system, data, check = value[0], value[1:7], value[7]
    a, b, c, d, e, f = list(data)
    if f in "012":
        body = a + b + f + "0000" + c + d + e
    elif f == "3":
        body = a + b + c + "00000" + d + e
    elif f == "4":
        body = a + b + c + d + "00000" + e
    else:
        body = a + b + c + d + e + "0000" + f
    return number_system + body + check


def normalize_code(raw: str, symbology: str, box: list | None = None) -> dict:
    kind = symbology.upper().replace("_", "-").replace(" ", "")
    aliases = {
        "EAN13": "EAN-13",
        "EAN8": "EAN-8",
        "UPCA": "UPC-A",
        "UPCE": "UPC-E",
        "QRCODE": "QR",
        "CODE-128": "CODE128",
    }
    kind = aliases.get(kind, kind)
    if kind == "EAN-13" and len(raw) == 13 and raw.startswith("0"):
        kind = "UPC-A"
    numeric = kind in ("EAN-13", "EAN-8", "UPC-A", "UPC-E", "GTIN")
    normalized = re.sub(r"[\s-]", "", raw) if numeric else raw.strip()
    if kind == "UPC-A" and len(normalized) == 13 and normalized.startswith("0"):
        normalized = normalized[1:]
    check = (
        valid_gtin(expand_upce(normalized) or "")
        if kind == "UPC-E"
        else valid_gtin(normalized)
        if numeric
        else None
    )
    contains_url = bool(
        re.match(r"^[a-zA-Z][a-zA-Z0-9+.-]*://", normalized)
    ) or urlsplit(normalized).scheme.lower() in ("http", "https")
    return {
        "symbology": kind,
        "raw_value": raw,
        "normalized_value": normalized,
        "checksum_valid": check,
        "bounding_box": box,
        "confidence": None,
        "type": "url" if contains_url else "gtin" if numeric else "text",
        "contains_url": contains_url,
        "warnings": (["INVALID_CHECKSUM"] if check is False else [])
        + (["QR_URL_NOT_FETCHED"] if contains_url else []),
        "source": "barcode_decoder",
    }


def decode_codes(image: Image.Image) -> dict:
    rgb = np.asarray(image)
    results = []
    # OpenCV handles common retail 1D formats and QR; ZXing expands to CODE128/UPC-E.
    try:
        ok, texts, points, _ = cv2.QRCodeDetector().detectAndDecodeMulti(rgb)
        if ok:
            for index, value in enumerate(texts):
                if value:
                    results.append(normalize_code(value, "QR", points[index].tolist()))
    except (cv2.error, ValueError):
        pass
    try:
        detector = cast(Any, cv2).barcode_BarcodeDetector()
        ok, texts, formats, points = detector.detectAndDecodeWithType(rgb)
        if ok:
            for index, value in enumerate(texts):
                if value:
                    results.append(
                        normalize_code(value, formats[index], points[index].tolist())
                    )
    except (AttributeError, cv2.error, ValueError):
        pass
    import zxingcpp

    allowed = [
        zxingcpp.BarcodeFormat.EAN13,
        zxingcpp.BarcodeFormat.EAN8,
        zxingcpp.BarcodeFormat.UPCA,
        zxingcpp.BarcodeFormat.UPCE,
        zxingcpp.BarcodeFormat.Code128,
        zxingcpp.BarcodeFormat.QRCode,
    ]
    for code in zxingcpp.read_barcodes(rgb, formats=zxingcpp.BarcodeFormats(allowed)):
        corners = [
            code.position.top_left,
            code.position.top_right,
            code.position.bottom_right,
            code.position.bottom_left,
        ]
        value = (
            (code.extra if isinstance(code.extra, dict) else {}).get("UPCE", code.text)
            if code.format == zxingcpp.BarcodeFormat.UPCE
            else code.text
        )
        result = normalize_code(value, str(code.format), [[p.x, p.y] for p in corners])
        result["source"] = "zxing_cpp"
        if not any(
            old["symbology"] == result["symbology"]
            and old["normalized_value"] == result["normalized_value"]
            for old in results
        ):
            results.append(result)
    return {"codes": results, "state": "detected" if results else "no_code"}
