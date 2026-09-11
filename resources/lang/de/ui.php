<?php

return [
    'modal' => [
        'heading' => ':label',
        'subheading' => 'ActionGuard-Check',
        'passed_summary' => 'Alle Checks erfolgreich. Die Aktion kann ausgeführt werden.',
        'failed_summary' => 'Die Aktion kann noch nicht ausgeführt werden. :failed von :total Checks fehlgeschlagen.',
        'cancel' => 'Abbrechen',
    ],
    'status' => [
        'passed' => 'Erfüllt',
        'required' => 'Erforderlich',
        'optional' => 'Optional',
    ],
    'post_save' => [
        'title' => 'Speichern verhindert: Kriterien nicht erfüllt',
        'body' => 'Der Datensatz befindet sich im geschützten Status „:state“. Folgende Kriterien sind nicht erfüllt:',
    ],
    'errors' => [
        'record_label' => 'Modelldatensatz',
        'record_missing' => 'Für die Prüfungen wurde kein Modelldatensatz bereitgestellt.',
        'check_label' => 'Prüffehler',
        'check_failed' => 'Die Prüfung konnte nicht abgeschlossen werden. Referenz: :reference',
        'invariant_label' => 'Statusinvariantenfehler',
        'invariant_failed' => 'Die Invariantenprüfung konnte nicht abgeschlossen werden. Referenz: :reference',
    ],
    'enterprise' => [
        'label' => 'Enterprise-Prüfung',
        'error_label' => 'Fehler der Enterprise-Prüfung',
        'unresolvable' => 'Die Enterprise-Prüfung konnte nicht aufgelöst werden.',
        'not_callable' => 'Die Enterprise-Prüfung stellt keine aufrufbare Prüfmethode bereit.',
        'invalid_result' => 'Die Enterprise-Prüfung hat ein inkompatibles Ergebnis zurückgegeben.',
        'failed' => 'Die Enterprise-Prüfung konnte nicht abgeschlossen werden. Referenz: :reference',
    ],
];
