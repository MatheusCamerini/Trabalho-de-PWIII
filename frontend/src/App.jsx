import {BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Login from './pages/Login';
import Register from './pages/Register';
import Feed from './pages/Feed';
import Post from './pages/Post';
var logado = false
export default function App() {
  return (
    <Router>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/cadastro" element={<Register />} />
        <Route path="/postagem" element={<Post />} />
        <Route path="/" element={logado? <Feed /> : <Login/>} />
      </Routes>
    </Router>
  )
}