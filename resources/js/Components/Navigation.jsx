import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X, ShoppingBag } from 'lucide-react';
import './navigation.css';

const Navigation = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const { auth } = usePage().props;

  useEffect(() => {
    const handleScroll = () => {
      if (window.scrollY > 50) {
        setIsScrolled(true);
      } else {
        setIsScrolled(false);
      }
    };
    
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const toggleMobileMenu = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
    if (!isMobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  };

  const closeMobileMenu = () => {
    setIsMobileMenuOpen(false);
    document.body.style.overflow = '';
  };

  return (
    <div className="navigation-container1">
      <nav className={`navigation-root ${isScrolled ? 'scrolled' : ''}`} style={{ background: isScrolled ? 'rgba(4, 4, 6, 0.8)' : 'rgba(4, 4, 6, 0.1)', padding: isScrolled ? '0.5rem 2rem' : '1rem 2rem', transition: 'all 0.3s ease' }}>
        <div className="navigation-container" style={{ padding: 0 }}>
          
          <div className="navigation-left">
            <Link href="/" onClick={closeMobileMenu}>
              <div aria-label="MORE Home" className="navigation-logo-link">
                <span className="navigation-thq-section-title-elm1 navigation-brand">
                  MORE
                </span>
              </div>
            </Link>
          </div>

          <div className="navigation-center" style={{ display: 'none' /* Will be flex on desktop via CSS, inline styles fallback */ }}>
            <ul className="navigation-links">
              <li className="navigation-item">
                <Link href="/" className="navigation-link">Novedades</Link>
              </li>
              <li className="navigation-item">
                <Link href="/" className="navigation-link">Categorías</Link>
              </li>
              <li className="navigation-item">
                <Link href="/" className="navigation-link">Tendencias</Link>
              </li>
            </ul>
          </div>

          <div className="navigation-right">
            <div className="navigation-actions">
              {auth?.user ? (
                <>
                  <Link href="/profile" className="btn-link" style={{ textTransform: 'uppercase', letterSpacing: '0.05em', fontSize: '0.85rem' }}>Perfil ({auth.user.nombre})</Link>
                  <Link href="/logout" method="post" as="button" className="btn-link" style={{ textTransform: 'uppercase', letterSpacing: '0.05em', fontSize: '0.85rem' }}>Cerrar sesión</Link>
                  <Link href="/cart" aria-label="Carrito" className="navigation-icon-btn" style={{ background: 'none', border: 'none', color: '#cbd5e1', cursor: 'pointer', display: 'flex', alignItems: 'center' }}>
                    <ShoppingBag size={20} />
                  </Link>
                </>
              ) : (
                <>
                  <Link href="/login" className="btn-link" style={{ textTransform: 'uppercase', letterSpacing: '0.05em', fontSize: '0.85rem' }}>Iniciar sesión</Link>
                  <Link href="/register" className="btn btn-outline" style={{ padding: '0.6rem 1.5rem', fontSize: '0.8rem' }}>Registrarse</Link>
                </>
              )}
            </div>
            
            <button
              onClick={toggleMobileMenu}
              aria-label="Menú"
              style={{ background: 'none', border: 'none', color: '#cbd5e1', cursor: 'pointer', display: 'none' /* Flex on mobile via CSS */ }}
              className="mobile-toggle-btn"
            >
              <Menu size={24} />
            </button>
          </div>

        </div>
      </nav>

      {/* Mobile Menu Overlay */}
      {isMobileMenuOpen && (
        <div className="navigation-mobile-overlay" style={{ position: 'fixed', inset: 0, backgroundColor: 'rgba(4, 4, 6, 0.95)', backdropFilter: 'blur(10px)', zIndex: 100, display: 'flex', flexDirection: 'column' }}>
          <div className="navigation-mobile-header" style={{ display: 'flex', justifyContent: 'space-between', padding: '1.5rem 2rem' }}>
            <span className="navigation-brand" style={{ color: '#f8fafc', fontWeight: 700, fontSize: '1.5rem', letterSpacing: '0.25em' }}>MORE</span>
            <button onClick={closeMobileMenu} style={{ background: 'none', border: 'none', color: '#f8fafc', cursor: 'pointer' }}>
              <X size={28} />
            </button>
          </div>
          <div className="navigation-mobile-content" style={{ display: 'flex', flexDirection: 'column', padding: '2rem', gap: '2rem' }}>
            <ul style={{ listStyle: 'none', padding: 0, display: 'flex', flexDirection: 'column', gap: '1.5rem', fontSize: '1.2rem' }}>
              <li><Link href="/" onClick={closeMobileMenu} style={{ color: '#cbd5e1', textDecoration: 'none' }}>Novedades</Link></li>
              <li><Link href="/" onClick={closeMobileMenu} style={{ color: '#cbd5e1', textDecoration: 'none' }}>Categorías</Link></li>
              <li><Link href="/" onClick={closeMobileMenu} style={{ color: '#cbd5e1', textDecoration: 'none' }}>Tendencias</Link></li>
            </ul>
            <div className="navigation-mobile-footer" style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '2rem' }}>
              {auth?.user ? (
                <>
                  <Link href="/profile" onClick={closeMobileMenu} className="btn btn-outline" style={{ textAlign: 'center', padding: '1rem' }}>Mi Perfil</Link>
                  <Link href="/cart" onClick={closeMobileMenu} className="btn btn-primary" style={{ textAlign: 'center', padding: '1rem', background: '#0d0d12', color: '#e2e8f0', border: '1px solid rgba(255,255,255,0.1)' }}>Carrito de Compras</Link>
                  <Link href="/logout" method="post" as="button" onClick={closeMobileMenu} className="btn-link" style={{ textAlign: 'center', padding: '1rem', marginTop: '1rem', color: '#ef4444' }}>Cerrar sesión</Link>
                </>
              ) : (
                <>
                  <Link href="/login" onClick={closeMobileMenu} className="btn btn-outline" style={{ textAlign: 'center', padding: '1rem' }}>Iniciar sesión</Link>
                  <Link href="/register" onClick={closeMobileMenu} className="btn btn-primary" style={{ textAlign: 'center', padding: '1rem' }}>Registrarse</Link>
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Navigation;
