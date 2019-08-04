import React from 'react';

const Login = () => (
  <div className="column is-4-widescreen is-offset-4-widescreen is-8-tablet is-offset-2-tablet">
    <article className="message">
      <div className="message-header">Entrar</div>
      <div className="message-body">
        <form action="">
          <div className="field">
            <label className="label">E-Mail</label>
            <div className="control has-icons-left">
              <input className="input is-danger" type="email" name="email" />
              <span className="icon is-small is-left">
                <i className="fa fa-envelope"></i>
              </span>
            </div>
            <p className="help is-danger">erro</p>
          </div>
          <div className="field">
            <label className="label">Palavra-passe</label>
            <div className="control has-icons-left">
              <input className="input is-danger" type="password" name="password" />
              <span className="icon is-small is-left">
                <i className="fa fa-key"></i>
              </span>
            </div>
            <p className="help is-danger">erro</p>
          </div>
          <div className="field is-grouped">
            <div className="control">
              <button className="button is-primary">Entrar</button>
            </div>
            <div className="control">
              <a href="#" className="button is-link">
                Redefinir a palavra-passe
              </a>
            </div>
          </div>
        </form>
      </div>
    </article>
  </div>
);

export default Login;
