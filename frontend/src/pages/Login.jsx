import NavBar from '../components/NavBar'
export default function Login() {
    return (
        <>
        <NavBar itens={[[{classes: 'li',ids: 'cadastro',href: '/cadastro',text: 'Cadastro'}],[{classes: 'li',ids: '',text: 'Projeto'}]]}/>
        <h1>Login</h1>
        </>
    )
}