import './componentsCss/NavBar.css';
import user from '../assets/image.png';
export default function NavBar() {
    const logado = true
    return (
        <nav id='navbar'>
            <div id="ul">
                <div className='cont'>
                    {logado ?
                        <a className='li' id="sair" href="/sair">Sair <i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                        :
                        (
                            <>
                                <a className='li' id="login" href="/login">Login</a>
                                <a className='li' id="cadastro" href="/cadastro">Cadastro</a>
                            </>
                        )}
                </div>
                <div className='cont'>
                    <a className='li' href="/">Feed</a>
                    <a className='li' href="/postagem">Postagem</a>
                </div>
                <div className="cont">
                    <div className='li'><img src={user} alt="User" /></div>
                </div>
            </div>
        </nav>
    )
}