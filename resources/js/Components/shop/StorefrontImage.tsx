import { ShoppingBag } from 'lucide-react';
import { ImgHTMLAttributes, useState } from 'react';

type StorefrontImageProps = ImgHTMLAttributes<HTMLImageElement> & {
    fallbackLabel?: string;
};

export default function StorefrontImage({
    src,
    alt,
    className = '',
    fallbackLabel = 'Mati Boutique',
    onError,
    ...props
}: StorefrontImageProps) {
    const [hasError, setHasError] = useState(false);

    if (!src || hasError) {
        return (
            <div
                aria-label={alt}
                role="img"
                className={[
                    className,
                    'flex items-center justify-center bg-[radial-gradient(circle_at_30%_20%,rgba(223,184,166,0.16),transparent_28%),linear-gradient(135deg,#11151b,#05070a_64%,#170f11)]',
                ].join(' ')}
            >
                <div className="flex flex-col items-center gap-3 px-4 text-center text-stone-300">
                    <ShoppingBag size={24} className="text-[rgb(236,205,191)]" />
                    <span className="text-[0.66rem] uppercase tracking-[0.24em]">
                        {fallbackLabel}
                    </span>
                </div>
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            className={className}
            onError={(event) => {
                setHasError(true);
                onError?.(event);
            }}
            {...props}
        />
    );
}
