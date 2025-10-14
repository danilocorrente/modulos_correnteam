# 📦 Serviço BuscaCEP

Serviço robusto para consulta de endereços brasileiros via **CEP**, com fallback automático entre **ViaCEP** e **BrasilAPI**, logs estruturados e retorno padronizado.

---

## 🚀 Instalação

1. Certifique-se de que o Guzzle está instalado (Laravel já usa por padrão):

   ```bash
   composer require guzzlehttp/guzzle
   ```

2. Adicione o serviço na pasta adequada (caso ainda não exista):

   ```
   app/Services/BuscaCEP.php
   ```

---

## 🧠 Uso básico

```php
use Danilocorrente\ModulosCorrenteam\Services\BuscaCEP;

$busca = new BuscaCEP();
$dados = $busca->buscar('09892100');

if ($dados['error']) {
    echo "❌ Erro: {$dados['error']}";
} else {
    echo "✅ Endereço encontrado via {$dados['source']}:\n";
    echo "{$dados['logradouro']}, {$dados['bairro']} - {$dados['localidade']}/{$dados['uf']}";
}
```

---

### 🧾 Exemplo de saída

```
✅ Endereço encontrado via viacep:
Rua Araraquara, Jordanópolis - São Bernardo do Campo/SP
```

Caso o ViaCEP falhe, o fallback ativa automaticamente:

```
✅ Endereço encontrado via brasilapi:
Rua Exemplo, Centro - Curitiba/PR
```

---

## 🪵 Log de Erros

Todos os erros e fallbacks são registrados em:

```
storage/logs/laravel.log
```

Exemplo:

```
[2025-10-14 17:12:03] local.WARNING: [BuscaCEP] ViaCEP não encontrou o CEP 99999999. Tentando fallback BrasilAPI...
[2025-10-14 17:12:03] local.ERROR: [BuscaCEP] Erro ao buscar CEP 00000000: CEP inválido
```

---

## 📦 Estrutura de retorno

| Chave | Tipo | Descrição |
|-------|------|------------|
| cep | string|null | CEP formatado |
| logradouro | string|null | Rua ou avenida |
| bairro | string|null | Bairro |
| localidade | string|null | Cidade |
| uf | string|null | Estado |
| complemento | string|null | Complemento |
| ddd | string|null | Código DDD |
| ibge | string|null | Código IBGE |
| source | string|null | Origem dos dados (`viacep` ou `brasilapi`) |
| error | string|null | Mensagem de erro, se houver |

---

## 💬 Exemplo de retorno bem-sucedido

```php
[
  "cep" => "09892-100",
  "logradouro" => "Rua Araraquara",
  "bairro" => "Jordanópolis",
  "localidade" => "São Bernardo do Campo",
  "uf" => "SP",
  "complemento" => "",
  "ddd" => "11",
  "ibge" => "3548708",
  "source" => "viacep",
  "error" => null
]
```

---

## 💣 Exemplo de retorno com erro

```php
[
  "cep" => null,
  "logradouro" => null,
  "bairro" => null,
  "localidade" => null,
  "uf" => null,
  "complemento" => null,
  "ddd" => null,
  "ibge" => null,
  "source" => null,
  "error" => "CEP inválido: 123"
]
```

---

## ⚡ Exemplo de uso no Livewire

```php
use Danilocorrente\ModulosCorrenteam\Services\BuscaCEP;

public function updatedCep()
{
    $buscaCep = new BuscaCEP();
    $dados = $buscaCep->buscar($this->cep);

    if (empty($dados['error'])) {
        $this->logradouro = $dados['logradouro'];
        $this->bairro = $dados['bairro'];
        $this->localidade = $dados['localidade'];
        $this->uf = $dados['uf'];
        $this->dispatch('notify', "Endereço preenchido via {$dados['source']}.");
    } else {
        $this->dispatch('notify', 'CEP não encontrado.');
    }
}
```

---

## 💡 Dica rápida

Quer testar rapidamente sem criar rota?

```bash
php artisan tinker
```

E dentro dele:

```php
use Danilocorrente\ModulosCorrenteam\Services\BuscaCEP;

$busca = new BuscaCEP();
print_r($busca->buscar('09892100'));
```

---

## 👨‍💻 Autor

**Danilo Corrente Luiz**  
Pacote: `danilocorrente/modulos-correnteam`  
Licença: MIT
