# Usage data via unofficial Cursor dashboard API

Le plan Pro+ n’accède pas à l’Admin API Enterprise (`api.cursor.com`, clé `crsr_*`, `filtered-usage-events`). Les tokens par Usage Event et le détail `chargedCents` sont néanmoins exposés par l’endpoint web que le dashboard Cursor appelle déjà : `POST cursor.com/api/dashboard/get-filtered-usage-events`, authentifié par session utilisateur.

Nous utilisons cette **Usage Data Source** non officielle, en **Local Deployment** uniquement. Les **Session Credentials** sont résolues en hybride (SQLite Cursor local, repli cookie `.env`). L’alternative Enterprise est documentée comme évolution possible mais hors MVP.

**Conséquences :** l’intégration peut casser sans préavis ; elle vit dans un module client isolé ; pas d’hébergement public ; pas de secrets dans le dépôt. Si l’équipe passe Enterprise plus tard, on pourra introduire un second adaptateur Admin API sans changer le contrat du module **Usage Summary**.
