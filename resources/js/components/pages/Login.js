import React, { useState } from 'react';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleSubmit = () => {
    event.preventDefault();
    axios
      .post('/api/session', {
        email,
        password
      })
      .then(function(response) {
        console.log(response);
      })
      .catch(function(error) {
        console.log(error);
      });
  };

  return (
    <div className="column is-4-widescreen is-offset-4-widescreen is-8-tablet is-offset-2-tablet">
      <article className="message">
        <div className="message-header">Entrar</div>
        <div className="message-body">
          <form onSubmit={handleSubmit}>
            <div className="field">
              <label className="label">E-Mail</label>
              <div className="control has-icons-left">
                <input
                  className="input is-danger"
                  type="email"
                  onChange={() => {
                    setEmail(event.target.value);
                  }}
                />
                <span className="icon is-small is-left">
                  <i className="fa fa-envelope"></i>
                </span>
              </div>
              <p className="help is-danger">erro</p>
            </div>
            <div className="field">
              <label className="label">Palavra-passe</label>
              <div className="control has-icons-left">
                <input
                  className="input is-danger"
                  type="password"
                  name="password"
                  onChange={() => {
                    setPassword(event.target.value);
                  }}
                />
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
};

export default Login;
