type SectionHeadingProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    align?: 'left' | 'center';
};

const titleStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function SectionHeading({
    eyebrow,
    title,
    description,
    align = 'left',
}: SectionHeadingProps) {
    const centered = align === 'center';

    return (
        <div
            className={[
                'flex flex-col gap-4',
                centered ? 'mx-auto max-w-3xl text-center' : 'max-w-2xl',
            ].join(' ')}
        >
            {eyebrow && (
                <span
                    className={[
                        'inline-flex w-fit items-center rounded-full border border-white/12 bg-white/[0.04] px-3 py-1 text-[0.68rem] uppercase tracking-[0.3em] text-stone-300',
                        centered ? 'mx-auto' : '',
                    ].join(' ')}
                >
                    {eyebrow}
                </span>
            )}

            <h2
                className="text-4xl leading-[0.94] tracking-[-0.02em] text-white sm:text-[3.4rem]"
                style={titleStyle}
            >
                {title}
            </h2>

            {description && (
                <p className="max-w-xl text-sm leading-7 text-stone-300 sm:text-[1.02rem]">
                    {description}
                </p>
            )}
        </div>
    );
}
