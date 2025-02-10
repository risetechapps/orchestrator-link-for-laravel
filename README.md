# Laravel Orchestrator Link

## 📌 Sobre o Projeto
O **Laravel Orchestrator Link** é um package para Laravel para facilitar as requições api para o Orchestrator.

## ✨ Funcionalidades
- 🔑 **Autenticação via Chave Api** usando uma chave api gerado na sua conta você consegue fazer requisições api
- 🏷 **Consultar CPF** você pode validar o cpf e pesquisar dados como nome e data de nascimento do cpf
- 🏷 **Consultar CNPJ** você pode validar o cnpj e pesquisar dados como razão social, socios, telefones e endereços do cnpj
- 🏷 **Consultar Endereços** você pode consutar endereços atraves do cep, como paises, estados e cidades
- 🏷 **Consultar Bancos** você pode consultar bancos e seus codigos de todos os bancos do territorio brasileiro
- 🏷 **Consultar Bancos** você pode consultar bancos e seus codigos de todos os bancos do territorio brasileiro
- 🏷 **Consultar Feriados** você pode consultar ferados em todo o territorio brasileiro
- 🏷 **Consultar Clima** você pode consultar clime de todas as cidades brasileiras
- 🏷 **Consultar Dominios** você pode verificar se dominio está em uso e seus nameserver


---

## 🚀 Instalação

### 1️⃣ Requisitos
Antes de instalar, certifique-se de que seu projeto atenda aos seguintes requisitos:
- PHP >= 8.0
- Laravel >= 10
- Composer instalado

### 2️⃣ Instalação do Package
Execute o seguinte comando no terminal:
```bash
  composer require risetechapps/orchestrator-link-for-laravel
```

### 3️⃣ Crie a Variável e coloque o seu token
```bash
  ORCHESTRATOR_TOKEN=xxxxxxx
```

---

## 🔑 Autenticação via API

### 🔹 Rota de CPF
**Endpoint:** `/services/cpf/{cpf}/{date}`
**Método:** `POST`

#### Exemplo de Requisição:
```json
{
    "cpf": "98765432100",
    "date": "00-00-0000"
}
```

#### Exemplo de Resposta:
```json
{
    "status": "true",
    "data": {
        "name": "Fulano 1",
        "cpf": "98765432100",
        "date": "00-00-0000"
    }
}
```


## 🛠 Contribuição
Sinta-se à vontade para contribuir! Basta seguir estes passos:
1. Faça um fork do repositório
2. Crie uma branch (`feature/nova-funcionalidade`)
3. Faça um commit das suas alterações
4. Envie um Pull Request

---

## 📜 Licença
Este projeto é distribuído sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

💡 **Desenvolvido por [Rise Tech](https://risetech.com.br)**

