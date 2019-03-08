EvasysPlugin
============

Plugin, um eine Schnittstelle zwischen dem LMS Stud.IP und EvaSys darzustellen. Es kann Daten aufbereiten und an EvaSys übersenden.

Man braucht dafür ein EvaSys und dafür einen Nutzer, für den die SOAP-Schnittstelle von EvaSys freigegeben ist.

**Notwendige SOAP Services für den EvaSys-SOAP-Nutzer:**

* `DeleteCourse`
* `DeleteSurvey`
* `GetEvaluationSummaryByParticipant`
* `GetFormsInfoByParams`
* `GetPswdsByParticipant`
* `GetSessionForUser`
* `GetSurveyIDsByParams`
* `GetUserIdsByParams`
* `InsertCourses`

## Support

Das Plugin wird von der Firma data-quest supported. Ansprechpartner ist Rasmus Fuhse <fuhse@data-quest.de>. 

## Lizenz

GPL 2