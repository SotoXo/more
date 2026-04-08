import type { ShopProduct } from '@/data/shop';
import { useState } from 'react';

export default function ProductGallery({ product }: { product: ShopProduct }) {
    const [selectedImage, setSelectedImage] = useState(
        product.gallery[0] ?? product.image,
    );

    return (
        <div className="space-y-4">
            <div className="overflow-hidden rounded-[2rem] border border-white/10 bg-[#0b1016]">
                <img
                    src={selectedImage}
                    alt={product.name}
                    className="h-[520px] w-full object-cover lg:h-[700px]"
                />
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
                                    ? 'border-white/28'
                                    : 'border-white/8 hover:border-white/16',
                            ].join(' ')}
                        >
                            <img
                                src={image}
                                alt={product.name}
                                className="h-28 w-full object-cover sm:h-36"
                            />
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
