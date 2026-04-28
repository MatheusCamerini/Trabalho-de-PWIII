import NavBar from '../components/NavBar'
export default function Register() {
    return (
        <>
        <NavBar itens={[[{classes: 'li',ids: 'login',href: '/login',text: 'Login'}],[{classes: 'li',ids: '',text: 'Projeto'}]]}/>
        <h1>Register</h1>
        </>
    )
}