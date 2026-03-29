import React from 'react';
import { Link } from '@inertiajs/react';
import './navigation.css';

const Navigation: React.FC = () => {
    return (
        <div className="navigation-container1">
            <nav className="navigation-root">
                <div className="navigation-container">
                    <div className="navigation-left">
                        <Link href="/">
                            <div aria-label="MORE Home" className="navigation-logo-link">
                                <span className="navigation-thq-section-title-elm1 navigation-brand">MORE</span>
                            </div>
                        </Link>
                    </div>
                    <div className="navigation-center">
                        <ul className="navigation-links">
                            <li className="navigation-item">
                                <Link href="/" className="navigation-link"><span>Novedades</span></Link>
                            </li>
                            <li className="navigation-item">
                                <Link href="/" className="navigation-link"><span>Categorías</span></Link>
                            </li>
                        </ul>
                    </div>
                    <div className="navigation-right">
                        <div className="navigation-actions">
                            <Link href="/login" className="btn-sm navigation-auth-link btn btn-link">
                                <span>Iniciar sesión</span>
                            </Link>
                            <Link href="/register" className="btn-sm navigation-auth-btn btn-primary btn">
                                <span>Registrarse</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    );
};

export default Navigation;
