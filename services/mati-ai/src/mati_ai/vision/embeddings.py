import math


def cosine_similarity(left: list[float], right: list[float]) -> float | None:
    if (
        not left
        or len(left) != len(right)
        or any(not math.isfinite(v) for v in left + right)
    ):
        return None

    # Scaling each vector by its largest absolute component preserves the
    # angle and avoids overflow/underflow with finite client-provided values.
    left_scale = max(abs(value) for value in left)
    right_scale = max(abs(value) for value in right)
    if left_scale == 0 or right_scale == 0:
        return None
    scaled_left = [value / left_scale for value in left]
    scaled_right = [value / right_scale for value in right]
    denominator = math.sqrt(
        math.fsum(value * value for value in scaled_left)
        * math.fsum(value * value for value in scaled_right)
    )
    score = math.fsum(
        a * b for a, b in zip(scaled_left, scaled_right, strict=True)
    ) / denominator
    return max(-1.0, min(1.0, score))
