import { Flower2, Globe2, Leaf, Snowflake, Sparkles, Sun, type LucideIcon } from 'lucide-react';

export type SeasonKey = 'winter' | 'autumn' | 'spring' | 'summer' | 'autumnWinter' | 'springSummer' | 'carnival' | 'worldCup';
export type SeasonMotif = 'flower' | 'petal' | 'leaf' | 'snowflake' | 'spark' | 'wave' | 'ribbon' | 'confetti' | 'dot' | 'hexagon' | 'ring' | 'streak';
export type SeasonMotion = 'float' | 'rise' | 'fall' | 'drift' | 'glow' | 'spinDrift';
export type SeasonParticleSpec = readonly [motif: SeasonMotif, size: number, colorIndex: number, opacity?: number];

export type SeasonTheme = {
    key: SeasonKey;
    label: string;
    icon: LucideIcon;
    gradient: string;
    accent: string;
    border: string;
    tint: string;
    particleColors: readonly string[];
    particles: readonly SeasonParticleSpec[];
    entrance?: 'carnival';
};

// Fixed viewport positions make the scene reproducible and keep detail near the edges.
export const seasonParticleSlots = [
    { left: '86%', top: '24%', duration: 15, delay: -3, enterDelay: 0, opacity: 0.36, depth: 'front' },
    { left: '9%', top: '18%', duration: 12, delay: -7, enterDelay: 140, opacity: 0.34, depth: 'front' },
    { left: '94%', top: '51%', duration: 16, delay: -5, enterDelay: 250, opacity: 0.31, depth: 'front' },
    { left: '5%', top: '73%', duration: 17, delay: -10, enterDelay: 350, opacity: 0.3, depth: 'front' },
    { left: '76%', top: '84%', duration: 14, delay: -8, enterDelay: 430, opacity: 0.34, depth: 'front' },
    { left: '70%', top: '9%', duration: 18, delay: -11, enterDelay: 160, opacity: 0.27, depth: 'back' },
    { left: '61%', top: '87%', duration: 14, delay: -4, enterDelay: 310, opacity: 0.29, depth: 'front' },
    { left: '87%', top: '69%', duration: 13, delay: -9, enterDelay: 470, opacity: 0.32, depth: 'front' },
    { left: '23%', top: '8%', duration: 19, delay: -6, enterDelay: 210, opacity: 0.24, depth: 'back' },
    { left: '63%', top: '56%', duration: 16, delay: -12, enterDelay: 390, opacity: 0.3, depth: 'front' },
    { left: '91%', top: '11%', duration: 22, delay: -13, enterDelay: 520, opacity: 0.1, depth: 'back' },
    { left: '44%', top: '91%', duration: 15, delay: -2, enterDelay: 610, opacity: 0.27, depth: 'front' },
    { left: '34%', top: '31%', duration: 12, delay: -8, enterDelay: 680, opacity: 0.26, depth: 'front' },
] as const;

export const seasonMotifMotion: Record<SeasonMotif, SeasonMotion> = {
    flower: 'float',
    petal: 'rise',
    leaf: 'drift',
    snowflake: 'fall',
    spark: 'glow',
    wave: 'glow',
    ribbon: 'spinDrift',
    confetti: 'fall',
    dot: 'float',
    hexagon: 'spinDrift',
    ring: 'float',
    streak: 'drift',
};

export const seasonThemes: Record<SeasonKey, SeasonTheme> = {
    winter: {
        key: 'winter', label: 'Invierno', icon: Snowflake,
        gradient: 'radial-gradient(ellipse 67% 75% at 3% 24%, rgba(54, 88, 121, 0.34), transparent 72%), radial-gradient(ellipse 62% 70% at 98% 73%, rgba(95, 128, 158, 0.22), transparent 74%)',
        accent: '#c1d6e6', border: 'rgba(159, 194, 221, 0.43)', tint: 'rgba(126, 163, 191, 0.17)',
        particleColors: ['#d4e5f0', '#9cbad0', '#e4edf3'],
        particles: [['snowflake', 25, 0], ['dot', 12, 2], ['snowflake', 21, 1], ['spark', 14, 2], ['snowflake', 19, 0], ['dot', 11, 1], ['snowflake', 24, 2], ['snowflake', 18, 0]],
    },
    autumn: {
        key: 'autumn', label: 'Otoño', icon: Leaf,
        gradient: 'radial-gradient(ellipse 67% 75% at 3% 24%, rgba(125, 70, 48, 0.36), transparent 72%), radial-gradient(ellipse 62% 70% at 98% 73%, rgba(153, 105, 62, 0.24), transparent 74%)',
        accent: '#dfb397', border: 'rgba(205, 151, 113, 0.43)', tint: 'rgba(160, 104, 70, 0.17)',
        particleColors: ['#d3a87e', '#bb816c', '#e0bd98'],
        particles: [['leaf', 27, 0], ['leaf', 21, 1], ['leaf', 23, 2], ['spark', 13, 2], ['leaf', 20, 0], ['dot', 11, 1], ['leaf', 29, 2], ['leaf', 22, 0], ['leaf', 25, 1]],
    },
    spring: {
        key: 'spring', label: 'Primavera', icon: Flower2,
        gradient: 'radial-gradient(ellipse 67% 75% at 3% 24%, rgba(62, 107, 72, 0.34), transparent 72%), radial-gradient(ellipse 62% 70% at 98% 73%, rgba(146, 101, 126, 0.22), transparent 74%)',
        accent: '#c0dac6', border: 'rgba(155, 197, 164, 0.42)', tint: 'rgba(124, 169, 137, 0.16)',
        particleColors: ['#b9d4a7', '#dbb4bf', '#e6d4bd', '#eedca8'],
        particles: [['flower', 33, 1], ['petal', 18, 2], ['petal', 16, 1], ['leaf', 25, 0], ['spark', 15, 3], ['leaf', 28, 0], ['flower', 29, 2], ['petal', 20, 1], ['leaf', 23, 0], ['flower', 51, 1, 0.1]],
    },
    summer: {
        key: 'summer', label: 'Verano', icon: Sun,
        gradient: 'radial-gradient(ellipse 67% 75% at 3% 24%, rgba(151, 110, 54, 0.33), transparent 72%), radial-gradient(ellipse 62% 70% at 98% 73%, rgba(179, 126, 79, 0.24), transparent 74%)',
        accent: '#e5ca97', border: 'rgba(220, 180, 111, 0.43)', tint: 'rgba(188, 145, 80, 0.17)',
        particleColors: ['#e7ce9f', '#d6ad7b', '#f0dfba'],
        particles: [['spark', 19, 0], ['dot', 12, 2], ['wave', 27, 1], ['spark', 14, 2], ['dot', 13, 0], ['wave', 32, 1], ['spark', 20, 2], ['dot', 11, 0], ['spark', 15, 1]],
    },
    autumnWinter: {
        key: 'autumnWinter', label: 'Otoño–Invierno', icon: Leaf,
        gradient: 'radial-gradient(ellipse 67% 75% at 3% 24%, rgba(119, 77, 63, 0.34), transparent 72%), radial-gradient(ellipse 62% 70% at 98% 73%, rgba(77, 108, 137, 0.23), transparent 74%)',
        accent: '#cfb8ad', border: 'rgba(185, 157, 146, 0.42)', tint: 'rgba(143, 119, 109, 0.16)',
        particleColors: ['#d0aa91', '#b8cad7', '#dbc8ba'],
        particles: [['leaf', 27, 0], ['snowflake', 22, 1], ['leaf', 23, 2], ['snowflake', 18, 1], ['spark', 14, 2], ['leaf', 27, 0], ['snowflake', 24, 1], ['leaf', 21, 2], ['snowflake', 20, 1], ['leaf', 24, 0]],
    },
    springSummer: {
        key: 'springSummer', label: 'Primavera–Verano', icon: Flower2,
        gradient: 'radial-gradient(ellipse 73% 77% at 0% 27%, rgba(64, 112, 54, 0.43), transparent 73%), radial-gradient(ellipse 67% 75% at 100% 73%, rgba(161, 117, 54, 0.3), transparent 74%), radial-gradient(ellipse 52% 62% at 70% 13%, rgba(148, 95, 118, 0.2), transparent 75%)',
        accent: '#d2d7ae', border: 'rgba(180, 191, 145, 0.44)', tint: 'rgba(151, 163, 114, 0.18)',
        particleColors: ['#b9d4a7', '#dfb2c0', '#efdbc4', '#eed697'],
        particles: [['flower', 36, 1], ['petal', 19, 2], ['petal', 16, 1], ['leaf', 27, 0], ['spark', 17, 3], ['leaf', 31, 0], ['flower', 30, 2], ['petal', 20, 1], ['leaf', 25, 0], ['spark', 16, 3], ['flower', 55, 1, 0.1], ['spark', 14, 3]],
    },
    carnival: {
        key: 'carnival', label: 'Carnavalera', icon: Sparkles,
        gradient: 'radial-gradient(ellipse 74% 76% at 0% 25%, rgba(113, 47, 76, 0.45), transparent 72%), radial-gradient(ellipse 66% 75% at 99% 72%, rgba(86, 57, 128, 0.33), transparent 73%), radial-gradient(ellipse 48% 60% at 72% 9%, rgba(146, 108, 54, 0.2), transparent 75%)',
        accent: '#dfbec9', border: 'rgba(199, 150, 175, 0.44)', tint: 'rgba(153, 104, 132, 0.17)',
        particleColors: ['#ecd39d', '#b9a4da', '#d899a6', '#8dabc8', '#b4c2a1', '#e9b4ca'],
        particles: [['ribbon', 60, 0], ['confetti', 10, 2], ['confetti', 11, 3], ['confetti', 9, 1], ['confetti', 10, 4], ['ribbon', 68, 1], ['confetti', 11, 0], ['confetti', 10, 5], ['confetti', 12, 2], ['ribbon', 55, 3], ['confetti', 10, 4, 0.28], ['spark', 17, 0], ['spark', 15, 5]],
        entrance: 'carnival',
    },
    worldCup: {
        key: 'worldCup', label: 'Mundial', icon: Globe2,
        gradient: 'radial-gradient(ellipse 73% 77% at 0% 27%, rgba(44, 91, 72, 0.31), transparent 73%), radial-gradient(ellipse 67% 75% at 100% 73%, rgba(98, 119, 91, 0.18), transparent 74%)',
        accent: '#bdd1bd', border: 'rgba(139, 181, 151, 0.38)', tint: 'rgba(94, 137, 108, 0.14)',
        particleColors: ['#a7c4ae', '#d9e4d5', '#86ae9d'],
        particles: [['ring', 26, 1], ['hexagon', 25, 0], ['streak', 29, 2], ['ring', 20, 0], ['hexagon', 22, 1], ['ring', 29, 2], ['hexagon', 27, 0], ['streak', 25, 1]],
    },
};

function normalizedWords(name: string): string[] {
    return name.trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toUpperCase().split(/[^A-Z]+/).filter(Boolean);
}

export function seasonThemeForName(name: string): SeasonTheme | null {
    const words = normalizedWords(name);
    if (words.length === 1) {
        const simple: Record<string, SeasonKey | null> = {
            INVIERNO: 'winter', OTONO: 'autumn', PRIMAVERA: 'spring',
            VERANO: 'summer', CARNAVALERA: 'carnival', MUNDIAL: 'worldCup', PERMANENTE: null,
        };
        const key = simple[words[0]];
        return key ? seasonThemes[key] : null;
    }

    if (words.length === 2) {
        const pair = new Set(words);
        if (pair.has('PRIMAVERA') && pair.has('VERANO')) return seasonThemes.springSummer;
        if (pair.has('OTONO') && pair.has('INVIERNO')) return seasonThemes.autumnWinter;
    }

    return null;
}

export function seasonThemeForSelection(names: string[]): SeasonTheme | null {
    return names.length === 1 ? seasonThemeForName(names[0]) : null;
}
