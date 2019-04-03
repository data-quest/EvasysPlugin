EvasysPlugin
============

Plugin, um eine Schnittstelle zwischen dem LMS Stud.IP und EvaSys darzustellen. Es kann Daten aufbereiten und an EvaSys übersenden.

Man braucht dafür ein EvaSys und einen Nutzer, für den die SOAP-Schnittstelle von EvaSys freigegeben ist.

#### Notwendige SOAP Services für den EvaSys-SOAP-Nutzer:

* `DeleteCourse`
* `DeleteSurvey`
* `GetEvaluationSummaryByParticipant`
* `GetFormsInfoByParams`
* `GetPswdsByParticipant`
* `GetSessionForUser`
* `GetSurveyIDsByParams`
* `GetUserIdsByParams`
* `InsertCourses`

#### Einstellungen des Plugins

Die Daten der SOAP-Nutzer müssen noch in Stud.IP eingegeben werden. Das macht man über die Oberfläche mit einem Root-Account unter Admin -> System -> Konfiguration. Alle relevanten Einstellungen sind bei `EVASYS_PLUGIN` zu finden:

* `EVASYS_URI`: Die Adresse des EvaSys-Servers wie `https://evasys.meinehochschule.de/` 
* `EVASYS_USER`: Nutzername des SOAP-Nutzers, der die obigen Services abfragen kann.
* `EVASYS_PASSWORD`: Passwort dieses SOAP-Nutzers im Klartext.


#### Weitere Dokumentationen des Plugins

Das EvaSys_plugin hat eine ganze Menge Einstellungen und Bedienmöglichkeiten. Das liegt daran, dass jede Hochschule andere Workflows hat und das Plugin für alle Hochschulen einsetzbar sein soll. Eine gute Dokumentation gibt es in diesem Dokument im `/doc` - Ordner:

[Doku ab Stud.IP 4.0.docx](https://github.com/data-quest/EvasysPlugin/raw/master/doc/Doku%20ab%20Stud.IP%204.0.docx)

## Support

Das Plugin wird von der Firma data-quest supported. Ansprechpartner ist Rasmus Fuhse <fuhse@data-quest.de>. 

## Lizenz

GPL 2