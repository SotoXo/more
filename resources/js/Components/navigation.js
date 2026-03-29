import React from 'react'
import { Link } from '@inertiajs/react'

import Script from 'dangerous-html/react'

import './navigation.css'

const Navigation = (props) => {
  return (
    <div className="navigation-container1">
      <nav className="navigation-root">
        <div className="navigation-container">
          <div className="navigation-left">
            <Link href="/">
              <div aria-label="MORE Home" className="navigation-logo-link">
                <span className="navigation-thq-section-title-elm1 navigation-brand">
                  MORE
                </span>
              </div>
            </Link>
          </div>
          <div className="navigation-center">
            <ul className="navigation-links">
              <li className="navigation-item">
                <Link href="/">
                  <div className="navigation-link">
                    <span>Novedades</span>
                  </div>
                </Link>
              </li>
              <li className="navigation-item">
                <Link href="/">
                  <div className="navigation-link">
                    <span>Categorías</span>
                  </div>
                </Link>
              </li>
              <li className="navigation-item">
                <Link href="/">
                  <div className="navigation-link">
                    <span>Tendencias</span>
                  </div>
                </Link>
              </li>
              <li className="navigation-item">
                <Link href="/">
                  <div className="navigation-link">
                    <span>Asesoría</span>
                  </div>
                </Link>
              </li>
            </ul>
          </div>
          <div className="navigation-right">
            <div className="navigation-actions">
              <Link href="/">
                <div className="btn-sm navigation-auth-link btn btn-link">
                  <span>Iniciar sesión</span>
                </div>
              </Link>
              <Link href="/">
                <div className="btn-sm navigation-auth-btn btn-primary btn">
                  <span>Registrarse</span>
                </div>
              </Link>
              <button
                aria-label="Carrito (solo lectura)"
                className="navigation-icon-btn"
              >
                <svg
                  width="24"
                  xmlns="http://www.w3.org/2000/svg"
                  height="24"
                  viewBox="0 0 24 24"
                >
                  <g
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  >
                    <path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"></path>
                    <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"></path>
                  </g>
                </svg>
              </button>
            </div>
            <button
              id="navToggle"
              aria-label="Abrir menú"
              aria-expanded="false"
              className="navigation-mobile-toggle"
            >
              <svg
                width="24"
                xmlns="http://www.w3.org/2000/svg"
                height="24"
                viewBox="0 0 24 24"
              >
                <path
                  d="M4 6h16M4 12h16M4 18h16"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
              </svg>
            </button>
          </div>
        </div>
      </nav>
      <div id="mobileOverlay" className="navigation-mobile-overlay">
        <div className="navigation-mobile-header">
          <span className="navigation-brand section-title">MORE</span>
          <button
            id="navClose"
            aria-label="Cerrar menú"
            className="navigation-mobile-close"
          >
            <svg
              width="24"
              xmlns="http://www.w3.org/2000/svg"
              height="24"
              viewBox="0 0 24 24"
            >
              <g
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <rect x="3" y="3" rx="2" width="18" height="18"></rect>
                <path d="M15 3v18M8 9l3 3l-3 3"></path>
              </g>
            </svg>
          </button>
        </div>
        <div className="navigation-mobile-content">
          <ul className="navigation-mobile-links">
            <li>
              <Link href="/">
                <div className="navigation-mobile-link">
                  <span>Novedades</span>
                </div>
              </Link>
            </li>
            <li>
              <Link href="/">
                <div className="navigation-mobile-link">
                  <span>Categorías</span>
                </div>
              </Link>
            </li>
            <li>
              <Link href="/">
                <div className="navigation-mobile-link">
                  <span>Tendencias</span>
                </div>
              </Link>
            </li>
            <li>
              <Link href="/">
                <div className="navigation-mobile-link">
                  <span>Asesoría</span>
                </div>
              </Link>
            </li>
          </ul>
          <div className="navigation-mobile-footer">
            <Link href="/">
              <div className="btn btn-outline navigation-full-btn btn-lg">
                <span>Iniciar sesión</span>
              </div>
            </Link>
            <Link href="/">
              <div className="btn-primary btn navigation-full-btn btn-lg">
                <span>Registrarse</span>
              </div>
            </Link>
          </div>
        </div>
      </div>
      <div className="navigation-container2">
        <div className="navigation-container3">
          <Script
            html={`<style>
@media (prefers-reduced-motion: reduce) {
.navigation-mobile-overlay {
  transition: none;
}
.navigation-link::after {
  transition: none;
}
}
</style>`}
          ></Script>
        </div>
      </div>
      <div className="navigation-container4">
        <div className="navigation-container5">
          <Script
            html={`<script defer data-name="navigation-logic">
(function(){
  const navToggle = document.getElementById("navToggle")
  const navClose = document.getElementById("navClose")
  const mobileOverlay = document.getElementById("mobileOverlay")
  const body = document.body

  const openMenu = () => {
    mobileOverlay.style.display = "flex"
    setTimeout(() => {
      mobileOverlay.classList.add("is-active")
      navToggle.setAttribute("aria-expanded", "true")
      body.style.overflow = "hidden"
    }, 10)
  }

  const closeMenu = () => {
    mobileOverlay.classList.remove("is-active")
    navToggle.setAttribute("aria-expanded", "false")
    body.style.overflow = ""

    setTimeout(() => {
      if (!mobileOverlay.classList.contains("is-active")) {
        mobileOverlay.style.display = "none"
      }
    }, 400)
  }

  navToggle.addEventListener("click", openMenu)
  navClose.addEventListener("click", closeMenu)

  const mobileLinks = mobileOverlay.querySelectorAll(".navigation-mobile-link")
  mobileLinks.forEach((link) => {
    link.addEventListener("click", closeMenu)
  })

  window.addEventListener("resize", () => {
    if (window.innerWidth > 991 && mobileOverlay.classList.contains("is-active")) {
      closeMenu()
    }
  })

  let lastScroll = 0
  const navRoot = document.querySelector(".navigation-root")

  window.addEventListener("scroll", () => {
    const currentScroll = window.pageYOffset

    if (currentScroll > 100) {
      navRoot.style.background = "var(--color-surface-elevated)"
      navRoot.style.padding = "0"
    } else {
      navRoot.style.background = "var(--color-surface)"
    }

    lastScroll = currentScroll
  })
})()
</script>`}
          ></Script>
        </div>
      </div>
    </div>
  )
}

export default Navigation
