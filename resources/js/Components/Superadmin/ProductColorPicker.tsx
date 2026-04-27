import { Pipette } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type ProductColorSample = {
    hex: string;
    relativeX: number;
    relativeY: number;
};

export default function ProductColorPicker({
    src,
    selectedSample,
    sampleRadius = 5,
    onPick,
}: {
    src: string | null;
    selectedSample?: ProductColorSample | null;
    sampleRadius?: number;
    onPick: (sample: ProductColorSample) => void;
}) {
    const imageRef = useRef<HTMLImageElement | null>(null);
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas || !src) {
            setReady(false);
            return;
        }

        let cancelled = false;
        const context = canvas.getContext('2d');

        if (!context) {
            setReady(false);
            return;
        }

        const image = new window.Image();
        image.onload = () => {
            if (cancelled) {
                return;
            }

            canvas.width = image.naturalWidth || image.width;
            canvas.height = image.naturalHeight || image.height;
            context.clearRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            setReady(true);
        };
        image.onerror = () => {
            if (!cancelled) {
                setReady(false);
            }
        };
        image.src = src;

        return () => {
            cancelled = true;
        };
    }, [src]);

    const handlePick = (event: React.MouseEvent<HTMLButtonElement>) => {
        const canvas = canvasRef.current;
        const image = imageRef.current;

        if (!canvas || !image || !ready) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        const bounds = image.getBoundingClientRect();
        const relativeX = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
        const relativeY = Math.max(0, Math.min(1, (event.clientY - bounds.top) / bounds.height));
        const centerX = Math.round(relativeX * (canvas.width - 1));
        const centerY = Math.round(relativeY * (canvas.height - 1));

        const startX = Math.max(0, centerX - sampleRadius);
        const startY = Math.max(0, centerY - sampleRadius);
        const width = Math.min(canvas.width - startX, (sampleRadius * 2) + 1);
        const height = Math.min(canvas.height - startY, (sampleRadius * 2) + 1);
        const pixels = context.getImageData(startX, startY, width, height).data;

        let red = 0;
        let green = 0;
        let blue = 0;
        let total = 0;

        for (let index = 0; index < pixels.length; index += 4) {
            if (pixels[index + 3] < 100) {
                continue;
            }

            red += pixels[index];
            green += pixels[index + 1];
            blue += pixels[index + 2];
            total += 1;
        }

        if (total === 0) {
            return;
        }

        const hex = `#${[red, green, blue]
            .map((value) => Math.round(value / total).toString(16).padStart(2, '0').toUpperCase())
            .join('')}`;

        onPick({
            hex,
            relativeX,
            relativeY,
        });
    };

    return (
        <div className="space-y-3">
            <button
                type="button"
                onClick={handlePick}
                disabled={!src || !ready}
                className="group relative block w-full overflow-hidden rounded-[1.2rem] border border-white/8 bg-[#070b10] disabled:cursor-not-allowed disabled:opacity-70"
            >
                {src ? (
                    <>
                        <img
                            ref={imageRef}
                            src={src}
                            alt="Vista previa principal"
                            className="h-[360px] w-full cursor-crosshair object-cover"
                        />
                        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.0),rgba(3,7,12,0.18))]" />
                        <div className="pointer-events-none absolute left-4 top-4 inline-flex items-center gap-2 rounded-full border border-white/12 bg-black/35 px-3 py-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-100 backdrop-blur">
                            <Pipette size={12} />
                            Haz clic sobre la tela
                        </div>
                        {selectedSample && (
                            <div
                                className="pointer-events-none absolute h-8 w-8 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white bg-transparent shadow-[0_0_0_4px_rgba(0,0,0,0.25)]"
                                style={{
                                    left: `${selectedSample.relativeX * 100}%`,
                                    top: `${selectedSample.relativeY * 100}%`,
                                }}
                            >
                                <span
                                    className="absolute left-1/2 top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/70"
                                    style={{ backgroundColor: selectedSample.hex }}
                                />
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex h-[360px] items-center justify-center text-stone-500">
                        <div className="text-center">
                            <Pipette size={26} className="mx-auto" />
                            <p className="mt-3 text-sm">Carga primero la imagen principal.</p>
                        </div>
                    </div>
                )}
            </button>

            <p className="text-sm leading-6 text-stone-400">
                Toma la muestra sobre una zona representativa de la tela. El sistema promedia una pequeña area para evitar ruido por brillo o sombra.
            </p>

            <canvas ref={canvasRef} className="hidden" />
        </div>
    );
}
