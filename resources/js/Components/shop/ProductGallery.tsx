import type { ShopProduct } from '@/types/shop';
import { useState } from 'react';

export default function ProductGallery({ product }: { product: ShopProduct }) {
    const [selectedImage, setSelectedImage] = useState(
        product.gallery[0] ?? product.image,
    );

    return (
        <div className="space-y-4">
            <div className="more-panel relative overflow-hidden rounded-[2rem]">
                <img
                    src={selectedImage}
                    alt={product.name}
                    className="h-[520px] w-full object-cover transition duration-[900ms] lg:h-[700px]"
                />
                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.08),transparent_30%,rgba(0,0,0,0.08)_100%)]" />
            </div>

            <div className="grid grid-cols-3 gap-3">
                {product.gallery.map((image) => {
                    const active = image === selectedImage;

                    return (
                        <button
                            key={image}
                            type="button"
                            onClick={() => setSelectedImage(image)}
                            className={[
                                'overflow-hidden rounded-[1.3rem] border transition duration-300',
                                active
                                    ? 'border-[rgba(181,122,109,0.44)] shadow-[0_18px_40px_rgba(181,122,109,0.14)]'
                                    : 'border-white/8 hover:border-[rgba(181,122,109,0.22)]',
                            ].join(' ')}
                        >
                            <img
                                src={image}
                                alt={product.name}
                                className="h-28 w-full object-cover transition duration-500 hover:scale-[1.03] sm:h-36"
                            />
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
