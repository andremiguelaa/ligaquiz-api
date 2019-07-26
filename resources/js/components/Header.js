import React from 'react';
import { Link } from 'react-router-dom';

const Header = () => (
  <header id="header">
    <nav className="navbar has-shadow is-light">
      <div className="container">
        <div className="navbar-brand">
          <Link to="/" className="navbar-item">
            <img src="/img/logo.png" alt="logo" />
          </Link>
          <div className="navbar-burger burger">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
        <div className="navbar-menu">
          <div className="navbar-start"></div>
          <div className="navbar-end">
            <div className="navbar-item has-dropdown">
              <Link to="/login" className="navbar-item">
                Entrar
              </Link>
              <Link to="/register" className="navbar-item">
                Registar
              </Link>
            </div>
          </div>
        </div>
      </div>
    </nav>
  </header>
);

export default Header;
