import React, { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import Navigation from '@/Components/Navigation.jsx';
import Footer from '@/Components/Footer.jsx';
import { motion } from 'framer-motion';
import useEmblaCarousel from 'embla-carousel-react';
import Autoplay from 'embla-carousel-autoplay';
import { Sparkles, TrendingUp } from 'lucide-react';
import './home.css';

const fadeUp = {
    hidden: { opacity: 0, y: 40 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.9, ease: [0.16, 1, 0.3, 1] } }
};

const staggerContainer = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { staggerChildren: 0.15 }
    }
};

const Home: React.FC = () => {
    const [emblaRefCategorias] = useEmblaCarousel({ loop: true, align: 'start' }, [Autoplay({ delay: 3500 })]);
    const [emblaRefTendencias] = useEmblaCarousel({ loop: true, align: 'center' }, [Autoplay({ delay: 2500 })]);

    return (
        <div className="home-container1">
            <Head>
                <title>MORE - Colección Exclusiva</title>
            </Head>

            <Navigation />

            <section className="hero-impactante">
                <div className="hero-video-wrapper">
                    <div className="ambient-bg">
                        <div className="ambient-glow glow-1"></div>
                        <div className="ambient-glow glow-2"></div>
                        <div className="ambient-glow glow-3"></div>
                    </div>
                    <div className="home-thq-hero-overlay-gradient-elm" />
                </div>
                <div className="hero-content-container">
                    <motion.h1
                        initial={{ opacity: 0, scale: 0.98, y: 15 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        transition={{ duration: 1.5, ease: [0.16, 1, 0.3, 1] }}
                        className="home-thq-hero-title-elm home-hero-title hero-title"
                    >
                        Colección Primavera 2025
                    </motion.h1>
                    <motion.p
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 1.2, delay: 0.3, ease: [0.16, 1, 0.3, 1] }}
                        className="home-thq-hero-subtitle-elm hero-subtitle"
                    >
                        Elegancia atemporal en cada pasarela. Diseño minimalista, calidad excepcional, estilo sin esfuerzo.
                    </motion.p>
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 1, delay: 0.6, ease: [0.16, 1, 0.3, 1] }}
                        className="hero-cta-wrapper"
                    >
                        <Link href="/login" className="btn btn-primary btn-lg" style={{ display: 'inline-flex', padding: '1rem 2.5rem', borderRadius: '4px', textDecoration: 'none', letterSpacing: '0.05em' }}>
                            Explorar Colección
                        </Link>
                    </motion.div>
                </div>
            </section>

            <motion.section
                variants={staggerContainer} initial="hidden" whileInView="visible" viewport={{ once: true, margin: "-100px" }}
                className="categorias-destacadas" style={{ padding: '6rem 0' }}
            >
                <motion.div variants={fadeUp} className="section-header" style={{ marginBottom: '3rem', textAlign: 'center' }}>
                    <h2 className="section-title">Categorías</h2>
                </motion.div>

                <div className="embla" ref={emblaRefCategorias} style={{ overflow: 'hidden', padding: '1rem 0' }}>
                    <div className="embla__container" style={{ display: 'flex' }}>
                        {[
                            { name: 'Hombre', img: 'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg' },
                            { name: 'Mujer', img: 'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg' },
                            { name: 'Accesorios', img: 'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg' },
                            { name: 'Casual', img: 'https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg' },
                            { name: 'Outerwear', img: 'https://images.pexels.com/photos/5119928/pexels-photo-5119928.jpeg' }
                        ].map((cat, idx) => (
                            <motion.div variants={fadeUp} key={idx} className="embla__slide category-card" style={{ flex: '0 0 35%', minWidth: '0', marginRight: '2rem', borderRadius: '8px', overflow: 'hidden', position: 'relative', height: '400px', cursor: 'pointer' }}>
                                <img alt={cat.name} src={cat.img + '?auto=compress&cs=tinysrgb&w=800'} style={{ width: '100%', height: '100%', objectFit: 'cover', transition: 'transform 0.5s ease' }} />
                                <div className="category-overlay" style={{ position: 'absolute', inset: '0', display: 'flex', alignItems: 'flex-end', padding: '2rem' }}>
                                    <span className="category-name" style={{ fontSize: '1.5rem' }}>{cat.name}</span>
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </motion.section>

            <motion.section
                variants={staggerContainer} initial="hidden" whileInView="visible" viewport={{ once: true, margin: "-100px" }}
                className="grid-productos" style={{ padding: '4rem 5%', background: 'linear-gradient(to bottom, transparent, rgba(4,4,6,0.6))' }}
            >
                <motion.div variants={fadeUp} className="section-header" style={{ marginBottom: '4rem' }}>
                    <h2 className="section-title" style={{ color: '#f8fafc' }}>Nuevos Lanzamientos</h2>
                </motion.div>
                <div className="product-grid-container" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '3rem' }}>
                    {[
                        { title: 'Blazer Estructurado', desc: 'Corte slim en lana italiana. Silueta refinada.', img: 'https://images.pexels.com/photos/7760003/pexels-photo-7760003.jpeg' },
                        { title: 'Pantalón Wide-Leg', desc: 'Caída fluida en algodón premium. Elegancia relajada.', img: 'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg' },
                        { title: 'Camisa Crisp White', desc: 'Algodón egipcio de alto gramaje. Básico perfecto.', img: 'https://images.pexels.com/photos/7682241/pexels-photo-7682241.jpeg' },
                        { title: 'Abrigo Minimal', desc: 'Líneas limpias, silueta oversized. Diseño puro.', img: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg' },
                        { title: 'Chaqueta Ligera', desc: 'Técnica y sofisticada. Capa intermedia esencial.', img: 'https://images.pexels.com/photos/7682120/pexels-photo-7682120.jpeg' },
                        { title: 'Botín Chelsea', desc: 'Piel italiana con suela de cuero. Atemporal.', img: 'https://images.pexels.com/photos/4936952/pexels-photo-4936952.jpeg' }
                    ].map((prod, idx) => (
                        <motion.div variants={fadeUp} key={idx} className="product-card" style={{ display: 'flex', flexDirection: 'column', borderRadius: '8px', overflow: 'hidden' }}>
                            <div className="product-image-box" style={{ width: '100%', height: '400px', overflow: 'hidden' }}>
                                <img alt={prod.title} src={prod.img + '?auto=compress&cs=tinysrgb&w=800'} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                            </div>
                            <div className="product-info" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '0.5rem', flexGrow: 1 }}>
                                <h3 className="section-subtitle">{prod.title}</h3>
                                <p className="section-content" style={{ flexGrow: 1 }}>{prod.desc}</p>
                                <div className="product-action" style={{ marginTop: '1rem' }}>
                                    <Link href="/login" className="btn btn-outline" style={{ display: 'inline-block', width: '100%', textAlign: 'center', padding: '0.75rem 0', textDecoration: 'none', borderRadius: '4px' }}>
                                        Ver Detalles
                                    </Link>
                                </div>
                            </div>
                        </motion.div>
                    ))}
                </div>
            </motion.section>

            <motion.section
                variants={staggerContainer} initial="hidden" whileInView="visible" viewport={{ once: true, margin: "-100px" }}
                className="tendencias-radar" style={{ padding: '6rem 0' }}
            >
                <div className="radar-container" style={{ margin: '0 5%' }}>
                    <div className="radar-header" style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '2rem' }}>
                        <h2 className="section-title">Tendencias</h2>
                        <TrendingUp color="#94a3b8" size={28} />
                    </div>

                    <div className="embla" ref={emblaRefTendencias} style={{ overflow: 'hidden' }}>
                        <div className="embla__container" style={{ display: 'flex', padding: '1rem 0' }}>
                            {['#Minimalismo', '#NeutralTones', '#Sostenible', '#TailoredFit', '#CapsuleWardrobe', '#TexturasMixtas'].map((tag, idx) => (
                                <div key={idx} className="embla__slide radar-item" style={{ flex: '0 0 auto', marginRight: '3rem', cursor: 'grab' }}>
                                    <span className="radar-tag" style={{ fontSize: '1.2rem', color: '#f8fafc', fontWeight: 500, borderBottom: '1px solid rgba(0, 229, 255, 0.4)', paddingBottom: '4px' }}>{tag}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </motion.section>

            <motion.section
                initial="hidden" whileInView="visible" viewport={{ once: true, margin: "-100px" }} variants={fadeUp}
                className="ia-moda" style={{ padding: '6rem 5%' }}
            >
                <div className="ia-container" style={{ display: 'flex', gap: '4rem', alignItems: 'center', background: '#0d0d12', borderRadius: '16px', border: '1px solid rgba(255,255,255,0.05)', padding: '4rem' }}>
                    <div className="ia-content" style={{ flex: '1' }}>
                        <h2 className="section-title" style={{ marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '1rem' }}>
                            <Sparkles color="#818cf8" size={28} /> Asesoría Personal
                        </h2>
                        <p className="section-content" style={{ fontSize: '1.1rem', lineHeight: '1.8', color: '#94a3b8', marginBottom: '2rem' }}>
                            Nuestro equipo de estilistas cura selecciones personalizadas basadas en tus preferencias. Descubre piezas que se adaptan perfectamente a tu estilo de vida y silueta.
                        </p>
                        <Link href="/register" className="btn btn-primary" style={{ display: 'inline-flex', padding: '0.8rem 2rem', borderRadius: '4px', textDecoration: 'none', fontWeight: 500 }}>
                            Solicitar Asesoría
                        </Link>
                    </div>
                    <div className="ia-visual" style={{ flex: '1', position: 'relative', borderRadius: '8px', overflow: 'hidden' }}>
                        <img
                            alt="Asesoría de Estilo"
                            src="https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=800"
                            style={{ width: '100%', height: 'auto', display: 'block' }}
                        />
                    </div>
                </div>
            </motion.section>

            <Footer />
        </div>
    );
};

export default Home;

