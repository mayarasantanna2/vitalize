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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="icon" type="image/png" href="img/logov.png">

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
            </ul>

            <a href="login.php" class="btnav">Entrar</a>
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

    </nav>

    <div class="painel">

        <div class="cabecalho-painel">
            <h1>Olá, Ana!</h1>
            <p>Acompanhe sua rotina hoje</p>
        </div>

        <div class="grade-painel">

            <!-- Humor -->
            <div class="card">
                <h3>Como vai seu humor?</h3>

                <div class="humor">

                    <button class="opcao-humor">
                        <div class="emoji roxo">
                            <i class="fa-regular fa-face-laugh"></i>
                        </div>
                        <span class="texto-emoji">Muito bem</span>
                    </button>

                    <button class="opcao-humor">
                        <div class="emoji lilas">
                            <i class="fa-regular fa-face-smile"></i>
                        </div>
                        <span class="texto-emoji">Bem</span>
                    </button>

                    <button class="opcao-humor">
                        <div class="emoji azul">
                            <i class="fa-regular fa-face-meh"></i>
                        </div>
                        <span class="texto-emoji">Mais ou menos</span>
                    </button>

                    <button class="opcao-humor">
                        <div class="emoji cinza">
                            <i class="fa-regular fa-face-frown"></i>
                        </div>
                        <span class="texto-emoji">Cansada</span>
                    </button>

                    <button class="opcao-humor">
                        <div class="emoji rosa">
                            <i class="fa-regular fa-face-sad-tear"></i>
                        </div>
                        <span class="texto-emoji">Triste</span>
                    </button>

                </div>
            </div>

            <!-- Sintomas -->
            <div class="card">
                <h3>Registro de sintomas</h3>
                <p class="subtitulo">Marque como você se sente hoje</p>

                <div class="sintomas">

                    <button class="sintoma">
                        <span class="material-symbols-outlined">sick</span>
                        Náusea
                    </button>

                    <button class="sintoma">
                        <span class="material-symbols-outlined">bolt</span>
                        Dor
                    </button>

                    <button class="sintoma">
                        <span class="material-symbols-outlined">bedtime</span>
                        Sono
                    </button>

                    <button class="sintoma">
                        <span class="material-symbols-outlined">restaurant</span>
                        Apetite
                    </button>

                    <button class="sintoma">
                        <span class="material-symbols-outlined">water_drop</span>
                        Hidratação
                    </button>

                    <button class="sintoma">
                        <span class="material-symbols-outlined">directions_walk</span>
                        Disposição
                    </button>

                </div>
            </div>

            <!-- Cardápio -->
            <div class="card">

                <div class="cabecalho-card">
                    <h3>Cardápio do dia</h3>
                </div>

                <div class="refeicoes">

                    <div class="refeicao">
                        <span class="categoria-refeicao cafe">Café da manhã</span>
                        <span class="texto-refeicao">
                            Vitamina de frutas com aveia, pão integral com queijo branco.
                        </span>
                    </div>

                    <div class="refeicao">
                        <span class="categoria-refeicao almoco">Almoço</span>
                        <span class="texto-refeicao">
                            Arroz integral, frango grelhado, legumes cozidos e salada verde.
                        </span>
                    </div>

                    <div class="refeicao">
                        <span class="categoria-refeicao lanche">Lanche</span>
                        <span class="texto-refeicao">
                            Iogurte natural com frutas ou castanhas.
                        </span>
                    </div>

                    <div class="refeicao">
                        <span class="categoria-refeicao jantar">Jantar</span>
                        <span class="texto-refeicao">
                            Sopa de legumes com frango desfiado e torrada integral.
                        </span>
                    </div>

                </div>

            </div>

            <!-- Dicas -->
            <div class="card">

                <div class="cabecalho-card">
                    <h3>Dicas de rotina</h3>
                </div>

                <div class="dicas">

                    <div class="dica">
                        <div class="icone-dica">
                            <i class="fa-solid fa-glass-water"></i>
                        </div>
                        <span>Beba bastante água ao longo do dia.</span>
                    </div>

                    <div class="dica">
                        <div class="icone-dica">
                            <i class="fa-solid fa-person-walking"></i>
                        </div>
                        <span>Faça atividades leves e respeite seus limites.</span>
                    </div>

                    <div class="dica">
                        <div class="icone-dica">
                            <i class="fa-solid fa-bed"></i>
                        </div>
                        <span>Descanse sempre que precisar.</span>
                    </div>

                    <div class="dica">
                        <div class="icone-dica">
                            <i class="fa-solid fa-pills"></i>
                        </div>
                        <span>Não se esqueça da sua medicação.</span>
                    </div>

                </div>

            </div>

            <!-- Consultas -->
            <div class="card card-consultas">

                <div class="cabecalho-card">

                    <div>
                        <h3>Próximas consultas e exames</h3>
                        <p class="subtitulo">Acompanhe seus próximos compromissos.</p>
                    </div>

                    <button id="abrirAgenda" class="btn-agenda">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Gerenciar
                    </button>

                </div>

                <div class="consultas">

                    <div class="consulta">

                        <div class="data-consulta">
                            <strong>20</strong>
                            <span>MAI</span>
                        </div>

                        <div class="info-consulta">
                            <h4>Consulta com Oncologista</h4>
                            <p>Dr. Carlos Almeida</p>
                        </div>

                        <div class="horario-consulta">
                            <strong>10:00</strong>
                            <span>Hospital São Lucas</span>
                        </div>

                    </div>

                    <div class="consulta">

                        <div class="data-consulta">
                            <strong>05</strong>
                            <span>JUN</span>
                        </div>

                        <div class="info-consulta">
                            <h4>Exame de Sangue</h4>
                            <p>Dr. João Nascimento</p>
                        </div>

                        <div class="horario-consulta">
                            <strong>08:00</strong>
                            <span>Laboratório Delboni</span>
                        </div>

                    </div>

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

    <div class="modal" id="modalAgenda">

        <div class="modal-conteudo agenda">

            <div class="modal-topo">

                <button class="fechar-modal" id="fecharAgenda">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>

                <h2 id="tituloFormulario">
                    Gerenciar Agenda
                </h2>

            </div>


            <!-- LISTA -->

            <div id="listaAgenda">

                <button class="btn-novo" id="novoItem">

                    <i class="fa-solid fa-plus"></i>

                    Nova consulta ou exame

                </button>


                <div class="item-agenda">

                    <div class="dados-agenda">

                        <h4>Consulta com Oncologista</h4>

                        <p>20/05/2026 • 10:00</p>

                        <span>Hospital São Lucas</span>

                    </div>

                    <div class="acoes">

                        <button class="editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>

                        <button class="excluir">
                            <i class="fa-solid fa-trash"></i>
                        </button>

                    </div>

                </div>


                <div class="item-agenda">

                    <div class="dados-agenda">

                        <h4>Exame de Sangue</h4>

                        <p>05/06/2026 • 08:00</p>

                        <span>Laboratório Delboni</span>

                    </div>

                    <div class="acoes">

                        <button class="editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>

                        <button class="excluir">
                            <i class="fa-solid fa-trash"></i>
                        </button>

                    </div>

                </div>

            </div>



            <!-- FORM -->

            <form id="formAgenda" class="form-consulta" style="display:none;">

                <div class="campo">

                    <label>Tipo</label>

                    <select>

                        <option>Consulta</option>
                        <option>Exame</option>

                    </select>

                </div>

                <div class="campo">

                    <label>Especialidade</label>

                    <input type="text" placeholder="Ex.: Oncologista">

                </div>

                <div class="campo">

                    <label>Médico</label>

                    <input type="text" placeholder="Nome do médico">

                </div>

                <div class="campo">

                    <label>Local</label>

                    <input type="text" placeholder="Hospital ou clínica">

                </div>

                <div class="campo">

                    <label>Data</label>

                    <input type="date">

                </div>

                <div class="campo">

                    <label>Horário</label>

                    <input type="time">

                </div>

                <div class="botoes-modal">

                    <button type="button" id="cancelarEdicao" class="btn-cancelar">
                        Cancelar
                    </button>

                    <button type="button" id="salvarAgenda" class="btn-salvar">
                        Salvar
                    </button>

                </div>

            </form>


        </div>

    </div>


    <script>

        const modal = document.getElementById("modalAgenda");

        const lista = document.getElementById("listaAgenda");

        const formulario = document.getElementById("formAgenda");

        const titulo = document.getElementById("tituloFormulario");
        function voltarInicioModal() {

            formulario.style.display = "none";
            lista.style.display = "block";

            titulo.innerHTML = "Gerenciar Agenda";

        }
        document.getElementById("abrirAgenda").onclick = () => {
            modal.classList.add("ativo");
            document.body.style.overflow = "hidden";
        };

        document.querySelector(".fechar-modal").onclick = () => {

            if (formulario.style.display === "block") {

                voltarInicioModal();

            } else {

                modal.classList.remove("ativo");
                document.body.style.overflow = "auto";

            }

        };

        modal.onclick = (e) => {

            if (e.target === modal) {

                modal.classList.remove("ativo");
                document.body.style.overflow = "auto";

            }

        };

        // NOVO
        document.getElementById("novoItem").onclick = () => {

            titulo.innerHTML = "Nova consulta";

            lista.style.display = "none";
            formulario.style.display = "block";
        };

        // EDITAR
        document.querySelectorAll(".editar").forEach(botao => {

            botao.onclick = () => {

                titulo.innerHTML = "Editar consulta";

                lista.style.display = "none";
                formulario.style.display = "block";

                // depois aqui você preencherá os campos com os dados vindos do banco
            }

        });

        // CANCELAR
        document.getElementById("cancelarEdicao").onclick = () => {

            voltarInicioModal();

        };
        // SALVAR
        document.getElementById("salvarAgenda").onclick = () => {

            alert("Consulta salva!");

            voltarInicioModal();

        };

        // EXCLUIR
        document.querySelectorAll(".excluir").forEach(botao => {

            botao.onclick = () => {

                if (confirm("Deseja excluir esta consulta?")) {

                    botao.closest(".item-agenda").remove();

                }

            }

        });
    </script>
    <script>
        // Humor: seleção única
        document.querySelectorAll('.opcao-humor').forEach(btn => {
            btn.addEventListener('click', () => {

                document.querySelectorAll('.opcao-humor').forEach(item => {
                    item.classList.remove('ativo');
                });

                btn.classList.add('ativo');
            });
        });
        document.querySelectorAll('.sintoma').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.classList.toggle('ativo');
            });
        });
        const toggle = document.querySelector(".menu-toggle");
        const menu = document.querySelector(".menu");

        toggle.addEventListener("click", () => {
            menu.classList.toggle("ativo");
        });
    </script>

</body>

</html>