import SectionHeading from '@/Components/landing/SectionHeading';
import ProductCard from '@/Components/shop/ProductCard';
import type { ShopProduct } from '@/data/shop';
import useEmblaCarousel from 'embla-carousel-react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

type ProductRailProps = {
    eyebrow: string;
    title: string;
    description?: string;
    products: ShopProduct[];
};

export default function ProductRail({
    eyebrow,
    title,
    description,
    products,
}: ProductRailProps) {
    const [railRef, railApi] = useEmblaCarousel({ align: 'start', loop: false });

    return (
        <section>
            <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <SectionHeading
                    eyebrow={eyebrow}
                    title={title}
                    description={description}
                />
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => railApi?.scrollPrev()}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-white transition hover:border-white/20 hover:bg-white/[0.08]"
                    >
                        <ChevronLeft size={18} />
                    </button>
                    <button
                        type="button"
                        onClick={() => railApi?.scrollNext()}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-white transition hover:border-white/20 hover:bg-white/[0.08]"
                    >
                        <ChevronRight size={18} />
                    </button>
                </div>
            </div>

            <div className="mt-10 overflow-hidden" ref={railRef}>
                <div className="-ml-4 flex">
                    {products.map((product) => (
                        <div
                            key={product.slug}
                            className="min-w-0 flex-[0_0_82%] pl-4 sm:flex-[0_0_48%] xl:flex-[0_0_31%]"
                        >
                            <ProductCard
                                product={product}
                                imageClassName="h-[360px] sm:h-[420px]"
                            />
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
