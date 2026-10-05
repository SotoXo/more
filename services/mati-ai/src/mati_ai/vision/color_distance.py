"""Perceptual distance for physical CIELAB values (L* in 0..100).

CIEDE2000 with kL=kC=kH=1, independently expressed from Sharma, Wu & Dalal:
https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/
Arrays broadcast, so pixels, clusters and catalog share the same calculation.
"""

import numpy as np


def delta_e_2000(left: np.ndarray, right: np.ndarray) -> np.ndarray:
    """Return Delta E 00; inputs end in [L*, a*, b*], never uint8 Lab."""
    left, right = np.broadcast_arrays(
        np.asarray(left, dtype=np.float64), np.asarray(right, dtype=np.float64)
    )
    l1, a1, b1 = np.moveaxis(left, -1, 0)
    l2, a2, b2 = np.moveaxis(right, -1, 0)
    c1, c2 = np.hypot(a1, b1), np.hypot(a2, b2)
    mean_c = (c1 + c2) / 2
    mean_c7 = mean_c**7
    g = 0.5 * (1 - np.sqrt(mean_c7 / (mean_c7 + 25**7)))
    ap1, ap2 = (1 + g) * a1, (1 + g) * a2
    cp1, cp2 = np.hypot(ap1, b1), np.hypot(ap2, b2)
    hp1 = np.mod(np.degrees(np.arctan2(b1, ap1)), 360)
    hp2 = np.mod(np.degrees(np.arctan2(b2, ap2)), 360)
    hp1 = np.where(cp1 == 0, 0, hp1)
    hp2 = np.where(cp2 == 0, 0, hp2)
    dl, dc = l2 - l1, cp2 - cp1
    raw_h = hp2 - hp1
    zero_chroma = cp1 * cp2 == 0
    dh = np.where(
        zero_chroma, 0,
        np.where(raw_h > 180, raw_h - 360, np.where(raw_h < -180, raw_h + 360, raw_h)),
    )
    d_h = 2 * np.sqrt(cp1 * cp2) * np.sin(np.radians(dh / 2))
    mean_l, mean_cp = (l1 + l2) / 2, (cp1 + cp2) / 2
    hue_sum = hp1 + hp2
    mean_hp = np.where(
        zero_chroma,
        hue_sum,
        np.where(
            np.abs(raw_h) <= 180,
            hue_sum / 2,
            np.where(hue_sum < 360, (hue_sum + 360) / 2, (hue_sum - 360) / 2),
        ),
    )
    t = (
        1 - 0.17 * np.cos(np.radians(mean_hp - 30))
        + 0.24 * np.cos(np.radians(2 * mean_hp))
        + 0.32 * np.cos(np.radians(3 * mean_hp + 6))
        - 0.20 * np.cos(np.radians(4 * mean_hp - 63))
    )
    sl = 1 + 0.015 * (mean_l - 50)**2 / np.sqrt(20 + (mean_l - 50)**2)
    sc, sh = 1 + 0.045 * mean_cp, 1 + 0.015 * mean_cp * t
    rotation_angle = 30 * np.exp(-((mean_hp - 275) / 25)**2)
    mean_cp7 = mean_cp**7
    rc = 2 * np.sqrt(mean_cp7 / (mean_cp7 + 25**7))
    rt = -np.sin(np.radians(2 * rotation_angle)) * rc
    dl, dc, d_h = dl / sl, dc / sc, d_h / sh
    return np.sqrt(np.maximum(0, dl**2 + dc**2 + d_h**2 + rt * dc * d_h))
