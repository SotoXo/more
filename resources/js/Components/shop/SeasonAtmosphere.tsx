import { seasonMotifMotion, seasonParticleSlots, seasonThemes, type SeasonKey, type SeasonMotif } from '@/lib/seasonTheme';
import { useEffect, useState, type CSSProperties } from 'react';

type SeasonAtmosphereProps = {
    season: SeasonKey | null;
};

function SeasonGlyph({ motif }: { motif: SeasonMotif }) {
    const shared = { viewBox: '0 0 32 32', 'aria-hidden': true as const, focusable: false as const };

    switch (motif) {
        case 'flower':
            return (
                <svg {...shared} fill="currentColor">
                    {[0, 72, 144, 216, 288].map((angle) => (
                        <ellipse key={angle} cx="16" cy="8.2" rx="4.5" ry="6.5" transform={'rotate(' + angle + ' 16 16)'} />
                    ))}
                    <circle cx="16" cy="16" r="3.1" />
                </svg>
            );
        case 'petal':
            return <svg {...shared} fill="currentColor"><path d="M8 25C8 13 16 5 25 5c1 10-5 20-17 20Z" /></svg>;
        case 'leaf':
            return (
                <svg {...shared} stroke="currentColor" strokeWidth="1.1" strokeLinecap="round" strokeLinejoin="round">
                    <path fill="currentColor" fillOpacity="0.66" d="M5 27C5 13 14 6 27 5c-1 13-9 22-22 22Z" />
                    <path d="M6 26c6-8 12-13 20-19" />
                </svg>
            );
        case 'snowflake':
            return (
                <svg {...shared} fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round">
                    <path d="M16 3v26M3 16h26M7 7l18 18M25 7 7 25" />
                    <path d="m12 7 4 4 4-4m-8 18 4-4 4 4M7 12l4 4-4 4m18 0-4 4 4 4" />
                </svg>
            );
        case 'spark':
            return <svg {...shared} fill="currentColor"><path d="m16 2 2.5 11.5L30 16l-11.5 2.5L16 30l-2.5-11.5L2 16l11.5-2.5L16 2Z" /></svg>;
        case 'wave':
            return <svg {...shared} fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="M3 11c5-5 9 5 14 0s9 5 12 0M3 20c5-5 9 5 14 0s9 5 12 0" /></svg>;
        case 'ribbon':
            return <svg {...shared} fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round"><path d="M6 3c20 0 21 8 9 12S7 22 26 29" /></svg>;
        case 'confetti':
            return <svg {...shared} fill="currentColor"><path d="M9 4 25 8 20 28 5 21Z" /></svg>;
        case 'dot':
            return <svg {...shared} fill="currentColor"><circle cx="16" cy="16" r="5" /></svg>;
        case 'hexagon':
            return <svg {...shared} fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round"><path d="M16 3 27 9.5v13L16 29 5 22.5v-13Z" /><path d="m16 3 2 10-8 6-5-9.5M27 9.5 18 13l5 8 4 1.5M16 29l7-8-13-2 6 10" opacity=".45" /></svg>;
        case 'ring':
            return <svg {...shared} fill="none" stroke="currentColor" strokeWidth="1.5"><circle cx="16" cy="16" r="12" /><circle cx="16" cy="16" r="4" opacity=".5" /></svg>;
        case 'streak':
            return <svg {...shared} fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round"><path d="M3 21c7-11 16-15 26-10M6 27c7-8 12-10 19-9" /></svg>;
    }
}

export default function SeasonAtmosphere({ season }: SeasonAtmosphereProps) {
    const [activeSeason, setActiveSeason] = useState<SeasonKey | null>(null);
    const [visibleSeasons, setVisibleSeasons] = useState<SeasonKey[]>([]);

    useEffect(() => {
        // Mount the incoming scene first so CSS can fade it against the outgoing one.
        setVisibleSeasons((previous) => Array.from(new Set([...previous, ...(season ? [season] : [])])));
        const activate = window.setTimeout(() => setActiveSeason(season), 40);
        const clearOutgoing = window.setTimeout(() => setVisibleSeasons(season ? [season] : []), 1900);

        return () => {
            window.clearTimeout(activate);
            window.clearTimeout(clearOutgoing);
        };
    }, [season]);

    return (
        <>
            <div className="season-background" aria-hidden="true">
                {Object.values(seasonThemes).map((theme) => (
                    <div
                        key={theme.key}
                        className="season-background__layer"
                        data-active={activeSeason === theme.key}
                        style={{ background: theme.gradient }}
                    />
                ))}
            </div>
            <div className="season-objects" aria-hidden="true">
                {visibleSeasons.map((key) => {
                    const theme = seasonThemes[key];
                    return (
                        <div
                            key={key}
                            className="season-objects__scene"
                            data-active={activeSeason === key}
                            data-entrance={theme.entrance ?? 'soft'}
                        >
                            {theme.particles.map(([motif, size, colorIndex, opacity], index) => {
                                const slot = seasonParticleSlots[index];
                                return (
                                    <span
                                        key={index}
                                        className={slot.depth === 'back' ? 'season-object season-object--back' : 'season-object'}
                                        data-tier={index < 5 ? 'mobile' : index < 8 ? 'tablet' : 'desktop'}
                                        style={{
                                            '--season-left': slot.left,
                                            '--season-top': slot.top,
                                            '--season-size': size + 'px',
                                            '--season-duration': slot.duration + 's',
                                            '--season-delay': slot.delay + 's',
                                            '--season-enter-delay': slot.enterDelay + 'ms',
                                            '--season-opacity': opacity ?? slot.opacity,
                                            color: theme.particleColors[colorIndex],
                                        } as CSSProperties}
                                    >
                                        <span className="season-object__glyph" data-motion={seasonMotifMotion[motif]}>
                                            <SeasonGlyph motif={motif} />
                                        </span>
                                    </span>
                                );
                            })}
                        </div>
                    );
                })}
            </div>
        </>
    );
}
