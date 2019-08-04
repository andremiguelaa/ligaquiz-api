import React from 'react';
import { NavLink } from 'react-router-dom';

const Header = () => (
  <header id="header">
    <nav className="navbar has-shadow is-light">
      <div className="container">
        <div className="navbar-brand">
          <NavLink to="/" className="navbar-item">
            <img src="/img/logo.png" alt="logo" />
          </NavLink>
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
              <NavLink to="/login" className="navbar-item">
                Login
              </NavLink>
              <NavLink to="/register" className="navbar-item">
                Register
              </NavLink>
            </div>
          </div>
        </div>
      </div>
    </nav>
  </header>
);

export default Header;
