<?php
/**
 * Biblioteki ładowane ręcznie (poza autoloaderem).
 * Nowe pliki z program/lib/ dopisuj tutaj.
 */

require_once BASE_PATH . '/program/lib/Db.php';
require_once BASE_PATH . '/program/lib/Tools.php';
require_once BASE_PATH . '/program/lib/Crypt.php';
require_once BASE_PATH . '/program/lib/PaginationHelper.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';

// Poczta — odkomentuj, gdy projekt wysyła maile (patrz MAIL_* w data.php).
// require_once BASE_PATH . '/program/lib/class.phpmailer.php';
// require_once BASE_PATH . '/program/lib/class.smtp.php';
// require_once BASE_PATH . '/program/lib/Mailer.php';

// Sloty na biblioteki doklejane per projekt:
// require_once BASE_PATH . '/program/lib/tcpdf/tcpdf.php';      // generowanie PDF
// require_once BASE_PATH . '/program/lib/RestClient.php';       // klient HTTP/REST
// require_once BASE_PATH . '/program/lib/FilterHelper.php';     // filtry listingów
