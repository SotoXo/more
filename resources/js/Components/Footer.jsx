import React from 'react';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Instagram, Twitter, Facebook, Linkedin, ArrowRight } from 'lucide-react';
import './footer.css';

const fadeUp = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: "easeOut" } }
};

const staggerContainer = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { staggerChildren: 0.1 }
  }
};

const Footer = () => {
  return (
    <div className="footer-container1">
      <footer className="footer-root">
        <div className="footer-container">
          <motion.div 
            className="footer-grid"
            variants={staggerContainer}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, margin: "-50px" }}
          >
            <motion.div variants={fadeUp} className="footer-brand-column footer-column">
              <Link href="/" style={{ textDecoration: 'none' }}>
                <div className="footer-logo">
                  <span>MORE</span>
                </div>
              </Link>
              <p className="footer-description section-content">
                Redefiniendo el futuro de la moda con inteligencia artificial.
                Estilo premium, curación inteligente y experiencias digitales inmersivas.
              </p>
              <div className="footer-social-wrapper">
                <a href="#" className="footer-social-link" aria-label="Instagram">
                  <Instagram size={20} />
                </a>
                <a href="#" className="footer-social-link" aria-label="Twitter">
                  <Twitter size={20} />
                </a>
                <a href="#" className="footer-social-link" aria-label="Facebook">
                  <Facebook size={20} />
                </a>
                <a href="#" className="footer-social-link" aria-label="LinkedIn">
                  <Linkedin size={20} />
                </a>
              </div>
            </motion.div>

            <motion.div variants={fadeUp} className="footer-column">
              <h2 className="footer-heading">Explorar</h2>
              <nav className="footer-nav">
                <Link href="/" className="footer-link">Nueva Colección</Link>
                <Link href="/" className="footer-link">Tendencias IA</Link>
                <Link href="/" className="footer-link">Exclusivos</Link>
                <Link href="/" className="footer-link">Sostenibilidad</Link>
              </nav>
            </motion.div>

            <motion.div variants={fadeUp} className="footer-column">
              <h2 className="footer-heading">Soporte</h2>
              <nav className="footer-nav">
                <Link href="/" className="footer-link">Centro de Ayuda</Link>
                <Link href="/" className="footer-link">Envíos y Devoluciones</Link>
                <Link href="/" className="footer-link">Guía de Tallas</Link>
                <Link href="/" className="footer-link">Contacto</Link>
              </nav>
            </motion.div>

            <motion.div variants={fadeUp} className="footer-column footer-newsletter-column">
              <h2 className="footer-heading">Newsletter</h2>
              <p className="footer-newsletter-text section-content">
                Suscríbete para recibir acceso anticipado y actualizaciones exclusivas.
              </p>
              <form 
                onSubmit={(e) => { e.preventDefault(); alert("Suscripción simulada (Demo)"); }}
                className="footer-form"
              >
                <div className="footer-input-group">
                  <input
                    type="email"
                    required
                    aria-label="Email para newsletter"
                    placeholder="Tu email"
                    className="footer-input"
                  />
                  <button
                    type="submit"
                    aria-label="Suscribirse"
                    className="footer-submit-btn"
                  >
                    <ArrowRight size={20} />
                  </button>
                </div>
              </form>
            </motion.div>
          </motion.div>

          <motion.div 
            initial={{ opacity: 0 }}
            whileInView={{ opacity: 1 }}
            viewport={{ once: true }}
            transition={{ duration: 1, delay: 0.5 }}
            className="footer-bottom"
          >
            <div className="footer-legal">
              <span className="footer-copyright">
                © {new Date().getFullYear()} MORE. Todos los derechos reservados.
              </span>
              <div className="footer-legal-links">
                <Link href="/" className="footer-legal-link">Privacidad</Link>
                <Link href="/" className="footer-legal-link">Términos</Link>
                <Link href="/" className="footer-legal-link">Cookies</Link>
              </div>
            </div>
            <div className="footer-status">
              <span className="footer-status-dot"></span>
              <span className="footer-status-text">Sistemas IA Operativos</span>
            </div>
          </motion.div>
        </div>
      </footer>
    </div>
  );
};

export default Footer;
