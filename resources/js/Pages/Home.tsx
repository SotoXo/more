import React from 'react';
import { Head } from '@inertiajs/react';
import Navigation from '@/Components/Navigation';
import Footer from '@/Components/Footer';
import './home.css';

const Home: React.FC = () => {
    return (
        <div className="home-container1">
            <Head>
                <title>Well Informed Green Hare - MORE</title>
                <meta property="og:title" content="Well Informed Green Hare" />
                <link rel="canonical" href="https://well-informed-green-hare-e1jpjw.teleporthq.app/" />
                <meta property="og:url" content="https://well-informed-green-hare-e1jpjw.teleporthq.app/" />
            </Head>

            <Navigation />

            <header className="more-header">
                <div className="header-container">
                    <div className="home-thq-brand-logo-elm brand-logo">
                        <span>MORE</span>
                    </div>
                    <div className="auth-controls">
                        <a href="#">
                            <div className="btn-link">
                                <span>Iniciar sesión</span>
                            </div>
                        </a>
                        <a href="#">
                            <div className="btn-sm btn-primary btn">
                                <span>Registrarse</span>
                            </div>
                        </a>
                    </div>
                </div>
            </header>

            <section className="hero-impactante">
                <div className="hero-video-wrapper">
                    <video
                        src="https://videos.pexels.com/video-files/19862866/19862866-hd_720_1280_30fps.mp4"
                        loop
                        muted
                        autoPlay
                        playsInline
                        className="hero-bg-video"
                    />
                    <div className="home-thq-hero-overlay-gradient-elm" />
                    <div className="home-thq-hero-video-controls-elm">
                        <button
                            aria-label="Pausar video"
                            data-action="toggle-video"
                            className="button video-control-btn"
                        >
                            <svg
                                fill="none"
                                width="20"
                                xmlns="http://www.w3.org/2000/svg"
                                height="20"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <rect x="6" y="4" width="4" height="16" />
                                <rect x="14" y="4" width="4" height="16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div className="hero-content-container">
                    <h1 className="home-thq-hero-title-elm home-hero-title hero-title">
                        Colección Primavera 2025
                    </h1>
                    <p className="home-thq-hero-subtitle-elm hero-subtitle">
                        Elegancia atemporal en cada pasarela. Diseño minimalista, calidad
                        excepcional, estilo sin esfuerzo.
                    </p>
                    <div className="hero-cta-wrapper">
                        <button disabled className="btn btn-outline">
                            <span>Explorar Colección</span>
                        </button>
                    </div>
                </div>
            </section>

            <section className="categorias-destacadas">
                <div className="section-header">
                    <h2 className="section-title">Categorías</h2>
                </div>
                <div className="carousel-outer">
                    <div className="carousel-track">
                        <div className="category-card">
                            <img
                                alt="Hombre"
                                src="https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1500"
                            />
                            <div className="category-overlay">
                                <span className="category-name">Hombre</span>
                            </div>
                        </div>
                        <div className="category-card">
                            <img
                                alt="Mujer"
                                src="https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1500"
                            />
                            <div className="category-overlay">
                                <span className="category-name">Mujer</span>
                            </div>
                        </div>
                        <div className="category-card">
                            <img
                                alt="Accesorios"
                                src="https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1500"
                            />
                            <div className="category-overlay">
                                <span className="category-name">Accesorios</span>
                            </div>
                        </div>
                        <div className="category-card">
                            <img
                                alt="Casual"
                                src="https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg?auto=compress&cs=tinysrgb&w=1500"
                            />
                            <div className="category-overlay">
                                <span className="category-name">Casual</span>
                            </div>
                        </div>
                        <div className="category-card">
                            <img
                                alt="Outerwear"
                                src="https://images.pexels.com/photos/5119928/pexels-photo-5119928.jpeg?auto=compress&cs=tinysrgb&w=1500"
                            />
                            <div className="category-overlay">
                                <span className="category-name">Outerwear</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="grid-productos">
                <div className="section-header">
                    <h2 className="section-title">Nuevos Lanzamientos</h2>
                </div>
                <div className="product-grid-container">
                    {[
                        { title: 'Blazer Estructurado', desc: 'Corte slim en lana italiana. Silueta refinada para cualquier ocasión.', img: 'https://images.pexels.com/photos/7760003/pexels-photo-7760003.jpeg?auto=compress&cs=tinysrgb&w=1500' },
                        { title: 'Pantalón Wide-Leg', desc: 'Caída fluida en algodón premium. Elegancia relajada.', img: 'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg?auto=compress&cs=tinysrgb&w=1500' },
                        { title: 'Camisa Crisp White', desc: 'Algodón egipcio de alto gramaje. El básico perfecto elevado.', img: 'https://images.pexels.com/photos/7682241/pexels-photo-7682241.jpeg?auto=compress&cs=tinysrgb&w=1500' },
                        { title: 'Abrigo Minimal', desc: 'Líneas limpias, silueta oversized. Protección sin comprometer el estilo.', img: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1500' },
                        { title: 'Chaqueta Ligera', desc: 'Técnica y sofisticada. La capa intermedia esencial.', img: 'https://images.pexels.com/photos/7682120/pexels-photo-7682120.jpeg?auto=compress&cs=tinysrgb&w=1500' },
                        { title: 'Botín Chelsea', desc: 'Piel italiana con suela de cuero. Diseño atemporal.', img: 'https://images.pexels.com/photos/4936952/pexels-photo-4936952.jpeg?auto=compress&cs=tinysrgb&w=1500' }
                    ].map((prod, idx) => (
                        <div key={idx} className="product-card">
                            <div className="product-image-box">
                                <img alt={prod.title} src={prod.img} />
                            </div>
                            <div className="product-info">
                                <h3 className="section-subtitle">{prod.title}</h3>
                                <p className="section-content">{prod.desc}</p>
                                <div className="product-action">
                                    <button title="Requiere iniciar sesión" disabled className="btn-sm btn btn-outline">
                                        Ver Detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="tendencias-radar">
                <div className="radar-container">
                    <div className="radar-header">
                        <h2 className="section-title">Tendencias</h2>
                        <div className="radar-icon-box">
                            <svg fill="none" width="24" height="24" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="m22 7-8.5 8.5-5-5L2 17" />
                                <path d="M16 7h6v6" />
                            </svg>
                        </div>
                    </div>
                    <div className="radar-scroller">
                        {['#Minimalismo', '#NeutralTones', '#Sostenible', '#TailoredFit', '#CapsuleWardrobe', '#TexturasMixtas'].map((tag, idx) => (
                            <div key={idx} className="radar-item">
                                <span className="radar-tag">{tag}</span>
                                <span className="radar-stat">{idx === 0 ? 'Tendencia Principal' : 'Estilo'}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="ia-moda">
                <div className="ia-container">
                    <div className="ia-content">
                        <h2 className="section-title">Asesoría Personal</h2>
                        <p className="section-content">
                            Nuestro equipo de estilistas cura selecciones personalizadas
                            basadas en tus preferencias. Descubre piezas que se adaptan
                            perfectamente a tu estilo de vida y silueta.
                        </p>
                        <ul className="ia-features">
                            <li className="ia-feature-item">
                                <div className="feature-icon">
                                    <svg fill="none" width="24" height="24" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M12 20v2m0-20v2m5 16v2m0-20v2M2 12h2m-2 5h2M2 7h2m16 5h2m-2 5h2M20 7h2M7 20v2M7 2v2" />
                                        <rect x="4" y="4" rx="2" width="16" height="16" />
                                        <rect x="8" y="8" rx="1" width="8" height="8" />
                                    </svg>
                                </div>
                                <span>Selecciones Curadas por Estilistas</span>
                            </li>
                        </ul>
                        <a href="#">
                            <div className="btn btn-outline">
                                <span>Solicitar Asesoría</span>
                            </div>
                        </a>
                    </div>
                    <div className="ia-visual">
                        <div className="ia-visual-backdrop" />
                        <img
                            alt="Asesoría de Estilo"
                            src="https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1500"
                            className="ia-demo-img"
                        />
                        <div className="ia-data-overlay">
                            <div className="data-point"><span>Estilistas Expertos</span></div>
                            <div className="data-point"><span>Servicio Personalizado</span></div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="llamado-accion-registro">
                <div className="cta-card">
                    <div className="cta-inner">
                        <h2 className="section-title">Únete a MORE</h2>
                        <p className="section-content">
                            Regístrate para acceder a lanzamientos exclusivos, asesoramiento
                            personalizado y una experiencia de compra diseñada para el amante
                            de la moda consciente.
                        </p>
                        <div className="cta-benefits">
                            <div className="benefit"><span>Envío Gratuito</span></div>
                            <div className="benefit"><span>Asesoría Personal</span></div>
                            <div className="benefit"><span>Acceso Exclusivo</span></div>
                        </div>
                        <div className="cta-button-box">
                            <a href="#">
                                <div className="btn-primary btn btn-lg">
                                    <span>Registrarse</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <Footer />
        </div>
    );
};

export default Home;
