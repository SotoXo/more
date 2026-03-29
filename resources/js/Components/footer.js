import React from 'react'
import { Link } from '@inertiajs/react'

import Script from 'dangerous-html/react'

import './footer.css'

const Footer = (props) => {
  return (
    <div className="footer-container1">
      <div className="footer-container2">
        <div className="footer-container3">
          <Script
            html={`<style>
@media (prefers-reduced-motion: reduce) {
.footer-status-dot {
  animation: none;
}
.footer-social-link:hover {
  transform: none;
}
}
</style>`}
          ></Script>
        </div>
      </div>
      <footer className="footer-root">
        <div className="footer-container">
          <div className="footer-grid">
            <div className="footer-brand-column footer-column">
              <Link href="/">
                <div className="footer-logo">
                  <span>MORE</span>
                </div>
              </Link>
              <p className="footer-description section-content">
                Redefiniendo el futuro de la moda con inteligencia artificial.
                Estilo premium, curación inteligente y experiencias digitales
                inmersivas.
              </p>
              <div className="footer-social-wrapper">
                <a href="#">
                  <div aria-label="Instagram" className="footer-social-link">
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
                      <rect
                        x="2"
                        y="2"
                        rx="5"
                        ry="5"
                        width="20"
                        height="20"
                      ></rect>
                      <path d="M16 11.37A4 4 0 1 1 12.63 8A4 4 0 0 1 16 11.37m1.5-4.87h.01"></path>
                    </svg>
                  </div>
                </a>
                <a href="#">
                  <div aria-label="Twitter" className="footer-social-link">
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
                      <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6c2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4c-.9-4.2 4-6.6 7-3.8c1.1 0 3-1.2 3-1.2"></path>
                    </svg>
                  </div>
                </a>
                <a href="#">
                  <div aria-label="Facebook" className="footer-social-link">
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
                      <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                    </svg>
                  </div>
                </a>
                <a href="#">
                  <div aria-label="LinkedIn" className="footer-social-link">
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
                      <rect x="2" y="9" width="4" height="12"></rect>
                      <circle r="2" cx="4" cy="4"></circle>
                      <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                    </svg>
                  </div>
                </a>
              </div>
            </div>
            <div className="footer-column">
              <h2 className="section-subtitle footer-heading">Explorar</h2>
              <nav className="footer-nav">
                <a href="#">
                  <div className="footer-link">
                    <span>Nueva Colección</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-link">
                    <span>Tendencias IA</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-link">
                    <span>Exclusivos</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-link">
                    <span>Sostenibilidad</span>
                  </div>
                </a>
              </nav>
            </div>
            <div className="footer-column">
              <h2 className="section-subtitle footer-heading">Soporte</h2>
              <nav className="footer-nav">
                <a href="#">
                  <div className="footer-link">
                    <span>Centro de Ayuda</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-link">
                    <span>Envíos y Devoluciones</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-link">
                    <span>Guía de Tallas</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-link">
                    <span>Contacto</span>
                  </div>
                </a>
              </nav>
            </div>
            <div className="footer-column footer-newsletter-column">
              <h2 className="section-subtitle footer-heading">Newsletter</h2>
              <p className="footer-newsletter-text section-content">
                Suscríbete para recibir acceso anticipado y actualizaciones de
                IA.
              </p>
              <form
                action="/subscribe"
                method="POST"
                data-form-id="32c48581-c33c-4c30-9bfe-c8f95c4faf8a"
                className="footer-form"
              >
                <div className="footer-input-group">
                  <input
                    type="email"
                    id="thq_textinput_YaAh"
                    name="textinput"
                    required="true"
                    aria-label="Email para newsletter"
                    placeholder="Tu email"
                    data-form-field-id="thq_textinput_YaAh"
                    className="footer-input"
                  />
                  <button
                    id="thq_button_qPQf"
                    name="button"
                    type="submit"
                    aria-label="Suscribirse"
                    data-form-field-id="thq_button_qPQf"
                    className="footer-submit-btn"
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
                      <path d="M5 12h14m-7-7l7 7l-7 7"></path>
                    </svg>
                  </button>
                </div>
              </form>
            </div>
          </div>
          <div className="footer-bottom">
            <div className="footer-legal">
              <span className="footer-copyright">
                © 2026 MORE. Todos los derechos reservados.
              </span>
              <div className="footer-legal-links">
                <a href="#">
                  <div className="footer-legal-link">
                    <span>Privacidad</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-legal-link">
                    <span>Términos</span>
                  </div>
                </a>
                <a href="#">
                  <div className="footer-legal-link">
                    <span>Cookies</span>
                  </div>
                </a>
              </div>
            </div>
            <div className="footer-status">
              <span className="footer-status-dot"></span>
              <span className="footer-status-text">Sistemas IA Operativos</span>
            </div>
          </div>
        </div>
      </footer>
      <div className="footer-container4">
        <div className="footer-container5">
          <Script
            html={`<style>
        @keyframes pulse {0% {transform: scale(1);
opacity: 1;}
50% {transform: scale(1.5);
opacity: 0.5;}
100% {transform: scale(1);
opacity: 1;}}
        </style> `}
          ></Script>
        </div>
      </div>
      <div className="footer-container6">
        <div className="footer-container7">
          <Script
            html={`<script>
            ;(function () {
              const footerForm = document.querySelector(".footer-form")
              if (footerForm) {
                footerForm.addEventListener("submit", (e) => {
                  const input = footerForm.querySelector(".footer-input")
                  if (input && input.value) {
                    const originalContent = footerForm.innerHTML
                    // Visual feedback for successful subscription
                    footerForm.innerHTML = \`
                      <div style="color: var(--color-on-surface); font-size: var(--font-size-sm); display: flex; align-items: center; gap: var(--spacing-sm);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        Suscripción exitosa. Bienvenido a MORE.
                      </div>
                    \`
                    // Reset form after 5 seconds if needed, though usually native submission handles redirect
                    // This is purely for UX demonstration of the "AI" feedback
                  }
                })
              }
              // Subtle reveal animation for footer columns
              const observerOptions = {
                threshold: 0.1,
                rootMargin: "0px",
              }
              const footerObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                  if (entry.isIntersecting) {
                    const columns = entry.target.querySelectorAll(".footer-column")
                    columns.forEach((col, index) => {
                      col.style.opacity = "0"
                      col.style.transform = "translateY(20px)"
                      col.style.transition = \`all 0.6s cubic-bezier(0.22, 1, 0.36, 1) \\\${index * 0.1}s\`
                      requestAnimationFrame(() => {
                        col.style.opacity = "1"
                        col.style.transform = "translateY(0)"
                      })
                    })
                    footerObserver.unobserve(entry.target)
                  }
                })
              }, observerOptions)
              const footerGrid = document.querySelector(".footer-grid")
              if (footerGrid) {
                footerObserver.observe(footerGrid)
              }
            })()
          </script>`}
          ></Script>
        </div>
      </div>
    </div>
  )
}

export default Footer
