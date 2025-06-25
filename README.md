[PHP__BADGE]: https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white
[GLPI__BADGE]: https://img.shields.io/badge/GLPI-3178C6?style=for-the-badge&logo=glpi&logoColor=white
[MYSQL__BADGE]: https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white
[CSS__BADGE]: https://img.shields.io/badge/CSS-1572B6?style=for-the-badge&logo=css&logoColor=white
[VERSION__BADGE]: https://img.shields.io/badge/Version-1.0.0-brightgreen?style=for-the-badge
[LICENSE__BADGE]: https://img.shields.io/badge/License-GPLv2+-orange?style=for-the-badge

# 📋 Plugin Informações Adicionais para Chamados - GLPI

![PHP][PHP__BADGE]
![GLPI][GLPI__BADGE]
![MYSQL][MYSQL__BADGE]
![CSS][CSS__BADGE]
![VERSION][VERSION__BADGE]
![LICENSE][LICENSE__BADGE]

---

Este é um plugin para GLPI que adiciona campos de informações adicionais aos chamados (tickets), permitindo o controle de responsável externo, status externo e prazo de atendimento.

---

## 🔍 Visão geral

O plugin permite que equipes de suporte acompanhem informações específicas relacionadas a fornecedores externos ou responsáveis terceirizados, oferecendo maior controle e visibilidade sobre o andamento dos chamados.

---

## 🚀 Funcionalidades

- ✅ **Campo Responsável Externo:** Texto livre para identificar pessoa/empresa responsável
- 📊 **Status Externo:** Controle de estado com opções predefinidas
- 📅 **Prazo de Atendimento:** Data limite para resolução externa
- 💾 **Persistência de dados:** Armazenamento em banco de dados dedicado
- 🔄 **Sincronização automática:** Dados salvos e carregados automaticamente
- 🔐 **Segurança:** Escape de dados e validação de entrada

---

## 🧪 Tecnologias utilizadas

- **Backend:** [PHP 8.1+](https://www.php.net/)
- **Framework:** [GLPI 10.0+](https://glpi-project.org/)
- **Banco de dados:** [MySQL 5.7+](https://www.mysql.com/)
- **Arquitetura:** Hook-based plugin system
- **Containerização:** [Docker](https://www.docker.com/) (desenvolvimento)

---

## ⚙️ Como instalar

### ✅ Pré-requisitos

- GLPI 10.0 ou superior
- PHP 8.1 ou superior
- MySQL 5.7 ou superior
- Acesso administrativo ao GLPI

### 🛠️ Passos para instalação

```bash
# 1. Clone o repositório no diretório de plugins do GLPI
cd /var/www/html/glpi/plugins/
git clone https://github.com/bantheus/plugin-glpi-plss.git aditionalinfo

# 2. Ajuste as permissões
chown -R www-data:www-data aditionalinfo/
chmod -R 755 aditionalinfo/

# 3. Acesse o GLPI como administrador
# 4. Vá em: Configurar > Plugins
# 5. Localize o plugin "Plugin Informações Adicionais para Chamados" e clique em "Instalar"
# 6. Após instalação, clique em "Ativar"
```

### 🐳 Instalação via Docker (desenvolvimento)

```bash
# 1. Clone o repositório
git clone https://github.com/bantheus/plugin-glpi-plss.git
cd plugin-glpi-plss

# 2. Suba o ambiente
docker compose up -d

# 3. Acesse o GLPI
http://localhost:8080

# 4. Instale o plugin via interface web
```

---

## 🗂️ Estrutura do projeto

```bash
aditionalinfo/
├── css/
│   └── additional-info.css  # CSS principal do plugin
├── locales/
│   └── en_GB.php            # Tradução para Inglês
│   └── pt_BR.php            # Tradução para Português
├── hook.php                 # Hooks e integrações com GLPI
├── setup.php                # Configuração, metadados e inicialização
├── ticketadditionalinfo.class.php  # Classe de persistência de dados
├── plugin.xml              # Manifesto oficial do plugin
```

## 🔧 Funcionalidades técnicas

### 📋 Hooks implementados

| Hook              | Descrição                                |
| ----------------- | ---------------------------------------- |
| `pre_item_form`   | Adiciona campos ao formulário de tickets |
| `pre_item_add`    | Captura dados antes de criar ticket      |
| `item_add`        | Processa dados após criar ticket         |
| `pre_item_update` | Captura dados antes de atualizar ticket  |
| `item_update`     | Processa dados após atualizar ticket     |
| `post_init`       | Inicialização e verificação do plugin    |

### 🗄️ Estrutura do banco

O plugin cria uma tabela dedicada para armazenar as informações adicionais:

```sql
CREATE TABLE `glpi_plugin_aditionalinfo_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tickets_id` int(11) NOT NULL DEFAULT '0',
  `external_responsible` varchar(255) DEFAULT NULL,
  `external_deadline` date DEFAULT NULL,
  `external_status` varchar(50) DEFAULT 'pendente',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tickets_id` (`tickets_id`),
  CONSTRAINT `fk_tickets_id` FOREIGN KEY (`tickets_id`) REFERENCES `glpi_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

**Campos da tabela:**

- `id`: Chave primária auto-incremento
- `tickets_id`: ID do ticket relacionado (FK para glpi_tickets)
- `external_responsible`: Nome do responsável externo
- `external_deadline`: Data limite para atendimento
- `external_status`: Status do atendimento (pendente, em_progresso, concluido)
- `date_creation`: Data de criação do registro
- `date_mod`: Data da última modificação

---

## 🧪 Como testar

### ✅ Testes manuais

1. **Criar novo ticket:**

   - Preencha os campos adicionais
   - Salve o ticket
   - Verifique se os dados foram salvos

2. **Editar ticket existente:**

   - Abra um ticket
   - Modifique os campos adicionais
   - Salve as alterações
   - Confirme a persistência

### 📊 Logs de debug

O plugin gera logs detalhados em:

```
/glpi/plugins/aditionalinfo/debug.log
```

---

## 🔒 Segurança e Boas Práticas

### Segurança Implementada

- **Escape de dados:** Todos os dados são escapados antes da inserção no banco
- **CSRF Protection:** Plugin compatível com proteção CSRF do GLPI
- **Prepared Statements:** Uso de consultas preparadas para evitar SQL Injection
- **Sanitização:** Dados de entrada são sanitizados adequadamente

---

## 🐛 Troubleshooting

### Problemas comuns

**Plugin não aparece:**

- Verifique permissões dos arquivos
- Confirme estrutura de diretórios
- Consulte logs do GLPI

**Dados não salvam:**

- Verifique logs em `debug.log`
- Confirme conexão com banco de dados
- Valide hooks registrados

---

## 👽 Desenvolvido por

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/bantheus">
        <img src="https://avatars.githubusercontent.com/u/70174902?v=4" width="100px;" alt="Matheus Schmidt"/><br>
        <sub>
          <b>Matheus Schmidt</b>
        </sub>
      </a>
    </td>
  </tr>
</table>
