import React, { Component, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter, Route, Switch } from 'react-router-dom';

import Header from './partials/Header';
import Home from './pages/Home';
import Login from './pages/Login';
import Register from './pages/Register';
import NoMatch from './pages/NoMatch';

const Page = props => {
  useEffect(() => {
    document.title = props.title;
  }, [props.title]);
  const { title, ...rest } = props;
  return <Route {...rest} />;
};

class App extends Component {
  render() {
    return (
      <BrowserRouter>
        <>
          <Header />
          <main>
            <section className="section">
              <div className="container">
                <div className="columns">
                  <Switch>
                    <Page title="Liga Quiz" exact path="/" component={Home} />
                    <Page title="Liga Quiz | Entrar" exact path="/login" component={Login} />
                    <Page
                      title="Liga Quiz | Registar"
                      exact
                      path="/register"
                      component={Register}
                    />
                    <Page title="Liga Quiz | Página não encontrada" component={NoMatch} />
                  </Switch>
                </div>
              </div>
            </section>
          </main>
        </>
      </BrowserRouter>
    );
  }
}

ReactDOM.render(<App />, document.getElementById('app'));
