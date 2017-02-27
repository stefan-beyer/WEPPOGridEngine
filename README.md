# WEPPOGridEngine

Die WEPPO Grid Engine baut auf dem WEPPO-Framwork auf und bietet ein erweitertes Templating-, Layouting und Content-Management-System. Alle Daten dazu (bis auf Templates selbst) werden in einer Datenbank gespeichert.
Layouts werden (derzeit) in Zeilen und Spalten aufgebaut. Wahlweise Zeilen oder Spalten zuerst. Die den Zellen wird dann ein Inhalt (Content) zugeordnet.

Sowohl einer Reihe (Row) als auch einer Zelle (Cell) kann eine sog. Provider-Klasse zugeordnet werden, das ist eine spezialisierte Klasse, die dann alles andere selbst und dynamisch regeln kann.

Inhalte werden wiederverwendbar abgelegt und bestehen aus einem klassischen Tempate mit mehrsprachige Content-Parametern.
Es kann auch besondere Content-Klassen geben, die spezialisierte Arbeit leisten (z.B. BlogPost).

Verwendet wird dieses Modul derzeit auf http://fermeculturesauvage.net
