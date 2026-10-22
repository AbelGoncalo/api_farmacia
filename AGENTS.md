# AGENTS.md

# Guia de Desenvolvimento Laravel 13

## Objetivo

Você é um desenvolvedor experiente em Laravel 13 e deve produzir código limpo, seguro, testável e preparado para produção.

Sempre siga as boas práticas do Laravel e a arquitetura definida neste documento.

---

# Tecnologias

Este projeto utiliza:

- PHP 8.4+
- Laravel 13
- MySQL
- Composer
- API REST
- JWT Authentication
- Eloquent ORM
- Pest (preferencialmente) ou PHPUnit
- Git

---

# Arquitetura

Toda funcionalidade deve seguir a seguinte arquitetura:

```
Controller
    ↓
Service
    ↓
Repository
    ↓
Model
```

### Controller

Responsável apenas por:

- Receber a requisição
- Chamar o Service
- Retornar a resposta

Nunca coloque regras de negócio dentro do Controller.

---

### Service

Responsável por:

- Regras de negócio
- Processos da aplicação
- Coordenação entre vários Repositories
- Transações no banco de dados

Todo processamento da aplicação deve ocorrer aqui.

---

### Repository

Responsável apenas pelo acesso aos dados.

Pode conter:

- Consultas Eloquent
- Filtros
- Paginação
- Pesquisas

Nunca implemente regras de negócio.

---

### Model

Responsável por:

- Relacionamentos
- Scopes
- Accessors
- Mutators
- Casts

Evite colocar lógica de negócio nos Models.

---

# Padrões de Código

Sempre siga:

- PSR-12
- Laravel Coding Style
- SOLID
- DRY
- KISS

Prefira sempre código simples e legível.

---

# Validação

Sempre utilize Form Requests.

Nunca valide diretamente no Controller.

Exemplos:

- StoreUserRequest
- UpdateUserRequest

---

# API Resources

Sempre utilize Resources para retornar dados.

Nunca retorne Models diretamente.

Exemplos:

- UserResource
- ProductResource
- OrderResource

---

# Banco de Dados

Sempre prefira:

- Eloquent
- Relacionamentos
- Query Builder

Evite SQL puro.

Utilize SQL bruto apenas quando realmente necessário.

---

# Tratamento de Erros

Utilize:

- Exceptions
- Custom Exceptions
- abort_if()
- abort_unless()

Nunca exponha erros internos da aplicação.

---

# Transações

Sempre utilize:

DB::transaction()

quando houver mais de uma operação no banco de dados.

---

# Autenticação

Utilize JWT Authentication.

Todas as rotas protegidas devem utilizar middleware.

Nunca exponha informações sensíveis.

---

# Autorização

Utilize:

- Policies
- Gates
- Spatie Permission (quando instalado)

Nunca implemente verificações de permissão espalhadas pelo código.

---

# API REST

Sempre respeite os padrões REST.

Métodos:

- GET
- POST
- PUT
- PATCH
- DELETE

Status HTTP:

- 200
- 201
- 204
- 400
- 401
- 403
- 404
- 409
- 422
- 500

---

# Nomeação

Classes

Sempre no singular.

Exemplos:

- UserService
- ProductRepository
- OrderController

Variáveis

camelCase

Métodos

camelCase

Tabelas

Plural

Colunas

snake_case

---

# Injeção de Dependência

Sempre utilize Dependency Injection.

Nunca instancie Services manualmente.

Exemplo:

```php
public function __construct(
    private readonly UserService $service
) {}
```

---

# Performance

Sempre:

- Utilize eager loading.
- Evite N+1 Queries.
- Utilize paginação.
- Selecione apenas as colunas necessárias.

---

# Segurança

Sempre:

- Validar entradas do utilizador.
- Utilizar Hash::make() para senhas.
- Proteger contra Mass Assignment.
- Nunca registrar senhas ou chaves em logs.
- Nunca confiar em dados recebidos do cliente.

---

# Testes

Sempre que possível escreva testes.

Priorize Pest.

Teste:

- Fluxo principal
- Validações
- Permissões
- Exceções

---

# Logs

Utilize o Log do Laravel.

Nunca deixe no código:

- dd()
- dump()
- var_dump()
- print_r()

---

# Configuração

Nunca utilize:

- URLs fixas
- Tokens fixos
- Senhas fixas
- Chaves diretamente no código

Sempre utilize:

- config()
- env()

---

# Migrations

Sempre utilize:

- foreignId()
- constrained()

Utilize:

- cascadeOnDelete()

quando fizer sentido.

Nunca altere migrations já executadas em produção.

Crie sempre uma nova migration.

---

# Seeders

Prefira Factories.

Os Seeders devem ser previsíveis e reproduzíveis.

---

# Filas (Queues)

Processos demorados devem ser executados em Jobs.

Nunca bloqueie requisições HTTP com tarefas pesadas.

---

# Upload de Arquivos

Sempre utilize o Storage do Laravel.

Nunca manipule caminhos de arquivos manualmente.

---

# Estrutura Padrão das Respostas

Sucesso

```json
{
    "success": true,
    "message": "Operação realizada com sucesso.",
    "data": {}
}
```

Erro

```json
{
    "success": false,
    "message": "Erro ao processar a solicitação.",
    "errors": {}
}
```

---

# Geração de Código

Sempre que gerar código:

- Gere arquivos completos.
- Utilize tipagem forte.
- Utilize Dependency Injection.
- Utilize Resources.
- Utilize Form Requests.
- Utilize Services.
- Utilize Repositories.
- Utilize boas práticas do Laravel.
- Mantenha métodos curtos.
- Mantenha classes pequenas e coesas.

---

# Nunca Faça

- Não coloque regras de negócio em Controllers.
- Não duplique código.
- Não retorne Models diretamente.
- Não ignore validações.
- Não ignore autorização.
- Não crie classes gigantes.
- Não escreva consultas SQL sem necessidade.
- Não utilize código comentado como histórico.

---

# Sempre Faça

- Escreva código limpo.
- Escreva código legível.
- Utilize nomes claros.
- Reutilize código.
- Documente apenas quando necessário.
- Priorize manutenção e escalabilidade.
- Siga as convenções oficiais do Laravel.

---

# Princípio Geral

Sempre escolha a solução mais simples, legível e idiomática do Laravel.

Em caso de dúvida:

1. Priorize a legibilidade.
2. Priorize a manutenção.
3. Siga a documentação oficial do Laravel.
4. Evite reinventar funcionalidades já fornecidas pelo framework.