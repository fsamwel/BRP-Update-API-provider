# Haal Centraal BRP Update API - provider implementatie
Dit is een implementatie van een provider van de Haal Centraal BRP Update API. Deze implementatie is gemaakt om de werking te demonstreren voor het uitproberen van de API.

De implementatie doet op een aantal punten niet wat een productie-implementatie wel zou moeten doen. Bijvoorbeeld:
- Controleren dat de gebruiker rechten heeft op de API/operatie. Voor productie is gebruik van een API key niet de meest voor de hand liggende keuze.
- Controleren dat een persoon waarvoor een volgindicatie wordt gezet of wijziging binnenkomt werkelijk bestaat. Wanneer een persoon niet bestaat zou de API een 404 moeten geven.
- Voor implementatie door een (leverancier van een) gemeente moet een volgindicatie worden doorgezet naar de binnengemeentelijke bron, dan wel naar GBA-V
- Op de juiste manier ontvangen, valideren en verwerken van wijzigingen. Bijvoorbeeld StUF npsLk01 berichten of Ag31 berichten. De API biedt endpoint /berichten aan, maar deze is alleen voor testdoeleinden geschikt.

## Beschikbare functies
De volgende endpoints zijn beschikbaar:

| Endpoint            | Functionaliteit                       |
|--- |--- |
| GET /volgindicaties | Raadpleeg alle volgindicaties van jou |
| GET /volgindicaties/{burgerservicenummer} | Raadpleeg de volgindicatie van jou op een persoon |
| PUT /volgindicaties/{burgerservicenummer} | Zet, wijzig of beëindig de volgindicatie op een persoon |
| GET /wijzigingen?vanaf={vanaf} | Vraag alle gevolgde personen waarbij sinds de vanaf-datum iets gewijzigd is |
| POST /berichten | Testfunctie voor het toevoegen van wijzigingen |

## Getting started
De API is beschikbaar via de url http://www.quality-of-service.nl/haalcentraal/api/update
Je kan de API ook installeren op een eigen webserver. Zie [Installatie](#installatie).

Bij een volgindicaties- of wijzigingen-request moet altijd de header x-api-key worden toegevoegd. Je mag zelf een API-key kiezen. Deze mag alleen uit cijfers en letters bestaan, geen andere tekens. De API-key zorgt ervoor dat je alleen volgindicaties en wijzigingen ziet die je zelf hebt gezet.

Je kan wijzigingen zien door zelf een wijziging te sturen. Hiervoor is het berichten endpoint gemaakt. Deze hoort eigenlijk niet bij de echte API, en is alleen gemaakt om te kunnen testen en experimenteren.
Je kan wijzigingen op twee manieren sturen:

- Als VOA bericht
- Als json array

## Installatie
De API vereist php 7.3 of hoger, of php 7.2 met de Apache module geïnstalleerd.
De API vereist een Mysql of MariaDb database (gebruikt mysqli).

1. Voer de queries in create_tables.sql uit om de gewenste tabellen te maken.
2. Wijzig de database creadentials en eventueel andere settings in config.php.

Importeer [BRP Update API.postman_collection.json](BRP Update API.postman_collection.json) in Postman om de API te testen.

### VOA berichten
De resource ontvangt in de payload van een POST bericht een of meerdere berichten zoals die in een GBA-V (VOA) bericht kunnen zitten. Een bericht bestaat uit meerdere regels. Elke regel heeft de volgende opbouw:

- (0, 7)   BERICHT-ID
- (8, 13)  RECORD-ID
- (14, 16) LENGTE
- (17, -)  INHOUD


Om een VOA bericht te sturen moet als Content header "text/plain" worden meegegeven.

De API zoekt in alle regels naar burgerservicenummers. Een regel bevat een burgerservicenummer, wanneer record-id = "010120".
Bijvoorbeeld voor het toevoegen van een wijziging op burgerservicenummer 999994669: 10089321<b>010120</b>009<b>999994669</b>

Deze API controleert niet of de berichten valide zijn. Er wordt ook geen onderscheid
gemaakt tussen de berichttypen waarin burgerservicenummers voorkomen. Dus elk 
burgerservicenummer in elk type bericht wordt als wijziging opgeslagen.
Voor de praktijd (productie) is dat waarschijnlijk niet correct. Voor testen 
is dat wel makkelijk, omdat gewoon rijen met als template "01234567010120009{burgerservicenummer}"
kunnen worden opgenomen.

Bij gemeenten zullen er meestal wijzigingen binnenkomen in de vorm van 
bijvoorbeeld StUF npsLk01 berichten, of in de vorm van Gv01-, Gv02-of Ag31-bericht
uit GBA-V.

### JSON array
Als payload van het POST bericht wordt een json array geleverd. In de array zitten objecten met burgerservicenummer en datum. 
Voordeel van deze vorm is dat je ook wijzigingen in het verleden kunt toevoegen en daarmee vanaf kunt testen.

Bijvoorbeeld:

```
[
	{
		"burgerservicenummer": "001008932",
		"datum": "2021-01-16"
	},
	{
		"burgerservicenummer": "100893201",
		"datum": "2021-01-16"
	},
	{
		"burgerservicenummer": "100893202",
		"datum": "2021-01-16"
	}
]
```

# Contact
Frank Samwel [frank@quality-of-service.nl](mailto:frank@quality-of-service.nl)

# Licentie
Copyright © Quality of Service Licensed under the [EUPL](https://eupl.eu)