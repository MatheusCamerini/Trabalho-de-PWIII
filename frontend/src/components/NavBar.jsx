import './componentsCss/NavBar.css';
import user from '../assets/image.png';
export default function NavBar(props) {
    var logado = false
    if (logado) {
        var baseItens = [
            [
                {
                    classes: 'li',
                    ids: 'sair',
                    href: '/sair',
                    text: <i class="fa-solid fa-arrow-right-from-bracket"></i>
                }

            ],
            [
                {
                    classes: 'li',
                    ids: '',
                    href: '/',
                    text: 'Feed'
                },
                {
                    classes: 'li',
                    ids: '',
                    href: '/postagem',
                    text: 'Postagem'
                }
            ],
            [
                {
                    classes: 'li',
                    ids: '',
                    href: '/user',
                    text: <img src={user} alt="User" />
                }
            ]
        ]
    }
    else{
        var baseItens = [
            [
                {
                    classes: 'li',
                    ids: 'login',
                    href: '/login',
                    text: 'Login'
                },
                {
                    classes: 'li',
                    ids: 'cadastro',
                    href: '/cadastro',
                    text: 'Cadastro'
                }

            ],
            [
                {
                    classes: 'li',
                    ids: '',
                    text: 'Projeto'
                }    
            ]
        ]
    }

    var itens = props.itens ?? baseItens
    return (
        <nav id='navbar'>
            <div id="ul">
                {
                    itens.map((item, indF) => (
                        <div className="cont" key={indF}>
                            {
                                item.map((itemChild, indC) => (
                                    <a key={indC} className={itemChild.classes} id={itemChild.ids} href={itemChild.href}>{itemChild.text}</a>
                                ))
                            }
                        </div>
                    ))
                }
            </div>
        </nav>
    )
}