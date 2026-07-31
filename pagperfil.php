<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitalize</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logov.png">

    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="header">

        <div class="logo-box">
            <img src="img/logov.png" alt="">
        </div>

        <div class="nav-bar">
            <ul class="menu">
                <li><a href="index.php">Início</a></li>
                <li><a href="sobregrupos.php">Grupos</a></li>
                <li><a href="doacao.php">Doação</a></li>
                <li><a href="saude.php">Saúde</a></li>
                <li><a href="relatos.php">Relatos</a></li>
                <li><a href="sobre.php">Sobre</a></li>
                <li><a href="grupos2.php">teste</a></li>

            </ul>

            <a href="login.php" class="btnav">Entrar</a>
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

    </nav>

    <div class="painel">

        <div class="cabecalho-perfil">
            <h1>Meu Perfil</h1>
            <p>Visualize e altere suas informações pessoais.</p>
        </div>

        <div class="perfil-card">

            <div class="perfil-topo">

                <div class="foto-perfil">

                    <img src="img/user.jpg" id="fotoPerfil">

                    <label for="novaFoto" class="editar-foto">
                        <i class="fa-solid fa-camera"></i>
                    </label>

                    <input type="file" id="novaFoto" hidden>

                </div>

                <div class="perfil-nome">

                    <h2>Mayara Sant' Anna</h2>

                </div>

                <button id="abrirPerfil" class="btn-agenda">
                    <i class="fa-solid fa-pen"></i>
                    Alterar dados
                </button>

            </div>

            <div class="perfil-info">

                <div class="info">
                    <span>Nome</span>
                    <strong>Mayara</strong>
                </div>

                <div class="info">
                    <span>Sobrenome</span>
                    <strong>Nascimento</strong>
                </div>

                <div class="info">
                    <span>Email</span>
                    <strong>mayara@gmail.com</strong>
                </div>

                <div class="info">
                    <span>Telefone</span>
                    <strong>(11) 94002-8922</strong>
                </div>

            </div>

        </div>

    </div>

    <footer class="footer">

        <div class="footer-redes">
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>

        <div class="footer-logo">
            <img src="img/logobrancacomp.png">
        </div>

        <div class="footer-info">
            <p>© 2026 Vitalize — Apoio ao tratamento contra o câncer</p>
            <p>Todos os direitos reservados | CNPJ 00.000.000/0001-00</p>
            <p>SAC 0800 000 0000</p>
        </div>

    </footer>


    <div class="modal" id="modalPerfil">

    <div class="modal-conteudo agenda">

        <div class="modal-topo">

            <button class="fechar-modal" id="fecharPerfil">
                <i class="fa-solid fa-arrow-left"></i>
            </button>

            <h2>Editar Perfil</h2>

        </div>

        <form id="formPerfil" class="form-consulta">

            <div class="campo full">

                <label>Foto de Perfil</label>

                <input type="file">

            </div>

            <div class="campo">
                <label>Nome</label>
                <input type="text" value="Mayara">
            </div>

            <div class="campo">
                <label>Sobrenome</label>
                <input type="text" value="Sant'Anna">
            </div>

            <div class="campo">
                <label>Email</label>
                <input type="email" value="mayara@email.com">
            </div>

            <div class="campo">
                <label>Telefone</label>
                <input type="tel" value="11999999999">
            </div>

            <div class="campo">
                <label>Nova senha</label>
                <input type="password">
            </div>

            <div class="campo">
                <label>Confirmar senha</label>
                <input type="password">
            </div>

            <div class="botoes-modal">

                <button type="button" class="btn-cancelar" id="cancelarPerfil">
                    Cancelar
                </button>

                <button type="submit" class="btn-salvar">
                    Salvar
                </button>

            </div>

        </form>

    </div>

</div>

    <script>
        const modalPerfil = document.getElementById("modalPerfil");

        document.getElementById("abrirPerfil").onclick = () => {

            modalPerfil.classList.add("ativo");
            document.body.style.overflow = "hidden";

        };

        document.getElementById("fecharPerfil").onclick = fecharPerfil;
        document.getElementById("cancelarPerfil").onclick = fecharPerfil;

        function fecharPerfil() {

            modalPerfil.classList.remove("ativo");
            document.body.style.overflow = "auto";

        }

        modalPerfil.onclick = (e) => {

            if (e.target == modalPerfil) {

                fecharPerfil();

            }

        };
    </script>


</body>


</html>