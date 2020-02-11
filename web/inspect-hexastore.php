<?php declare(strict_types=1);
use Kepawni\Limerick\Hexastore;
use Predis\Client;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

$redis = new Client(
    sprintf("tcp://%s:%d", REDIS_HOST, REDIS_PORT),
    ['parameters' => ['database' => REDIS_DB_INDEX, 'password' => REDIS_PASSWORD]]
);
$hexastore = new Hexastore($redis, HEXASTORE_TRIPLES_KEY, HEXASTORE_TRIPLE_SEPARATOR, HEXASTORE_TRIPLE_ESCAPE);
$entity = isset($_GET['e']) ? $redis->get(HEXASTORE_OBJECT_KEY_PREFIX . $_GET['e']) : null;
$rehydrated = unserialize($entity);
$entity = $rehydrated !== false && !($rehydrated instanceof __PHP_Incomplete_Class) ? $rehydrated : $entity;

function out(): void
{
    echo htmlspecialchars(sprintf(...func_get_args()));
}

function isId(string $value): bool
{
    return boolval(preg_match('<^([\\da-f]{40}|[\\da-f]{8}(-[\\da-f]{4}){4}[\\da-f]{8})$>', $value));
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Hexastore</title>
        <style type="text/css">
            * {
                font-family: "Fira Sans Compressed", Helvetica, Arial, sans-serif;
                font-size: 14px;
                text-align: left;
            }

            a {
                padding: 0 3px;
                text-decoration: none;
                color: #000;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            a:hover {
                text-decoration: underline;
                color: #00a;
            }

            a.id {
                padding: 2px 3px;
                font-family: "Fira Code", monospace;
                font-size: 10px;
                background-color: #edc;
                border-radius: 3px;
            }

            a.literal {
                background-color: #cde;
                border-radius: 3px;
            }

            a.predicate {
                background-color: #ddd;
                border-radius: 3px;
            }
            
            div.arrow {
                border: 14px solid transparent;
                border-top: 20px #888;
                border-bottom-width: 0;
                width: 0;
                margin: 0 auto
            }
        </style>
    </head>
    <body>
        <?php if ($entity && ($entity instanceof JsonSerializable)): ?>

        <pre><?php out('%s', json_encode($entity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
        <?php elseif ($entity && (is_object($entity) || is_array($entity))): ?>

        <table>
            <tbody>
                <?php foreach ($entity as $key => $value): ?>

                <tr>
                    <th><?php out($key); ?></th>
                    <td>
                        <a title="<?php out($value); ?>" class="<?php echo isId($value) ? 'id' : 'literal'; ?>"
                           href="?<?php out('%s=%s', isId($value) ? 'e' : 'o', rawurlencode($value)); ?>"><?php out($value); ?></a>
                    </td>
                </tr>
                <?php endforeach ?>

            </tbody>
        </table>
        <?php elseif (substr_count(trim($entity), "\n")): ?>

        <pre><?php out('%s', $entity); ?></pre>
        <?php endif ?>

        <p><?php out('%s', $entity); ?></p>
        <?php endif ?>

        <?php if ($entity): ?>

        <hr />
        <?php endif ?>

        <table>
            <tbody>
                <?php if ($entity): ?>

                <tr>
                    <td colspan="4"></td>
                    <td><div class="arrow"></div></td>
                    <td colspan="4"></td>
                </tr>
                <?php endif;
                $searchResults = $hexastore->find(
                    $_GET['e'] ?? null,
                    $_GET['p'] ?? ($_GET ? null : HEXASTORE_FALLBACK_PREDICATE),
                    $_GET['o'] ?? null
                );
                foreach ($searchResults as [$s, $p, $o]): ?>

                <tr>
                    <td colspan="4"></td>
                    <td><a title="<?php out($s); ?>" class="id"
                           href="?e=<?php out('%s', rawurlencode($s)); ?>"><?php out($s); ?></a></td>
                    <td><a title="<?php out('%s %s', $s, $p); ?>"
                           href="<?php out('?e=%s&p=%s', rawurlencode($s), rawurlencode($p)); ?>">⇨</a></td>
                    <td><a title="<?php out($p); ?>" class="predicate"
                           href="?p=<?php out('%s', rawurlencode($p)); ?>"><?php out($p); ?></a></td>
                    <td><a title="<?php out('%s %s', $p, $o); ?>"
                           href="<?php out('?p=%s&o=%s', rawurlencode($p), rawurlencode($o)); ?>">⇨</a></td>
                    <td><a title="<?php out($o); ?>" class="<?php echo isId($o) ? 'id' : 'literal'; ?>"
                           href="?<?php out('%s=%s', isId($o) ? 'e' : 'o', rawurlencode($o)); ?>"><?php out($o); ?></a></td>
                </tr>
                <?php endforeach ?>

            </tbody>
                <?php if (isset($_GET['e']) && !isset($_GET['o'])): ?>

                <tbody>

                    <?php
                    foreach ($hexastore->find(null, $_GET['p'] ?? null, $_GET['e']) as [$s, $p, $o]): ?>

                    <tr>
                        <td><a title="<?php out($s); ?>" class="id"
                               href="?e=<?php out('%s', rawurlencode($s)); ?>"><?php out($s); ?></a></td>
                        <td><a title="<?php out('%s %s', $s, $p); ?>"
                               href="<?php out('?e=%s&p=%s', rawurlencode($s), rawurlencode($p)); ?>">⇨</a></td>
                        <td><a title="<?php out($p); ?>" class="predicate"
                               href="?p=<?php out('%s', rawurlencode($p)); ?>"><?php out($p); ?></a></td>
                        <td><a title="<?php out('%s %s', $p, $o); ?>"
                               href="<?php out('?p=%s&o=%s', rawurlencode($p), rawurlencode($o)); ?>">⇨</a></td>
                        <td><a title="<?php out($o); ?>" class="<?php echo isId($o) ? 'id' : 'literal'; ?>"
                               href="?<?php out('%s=%s', isId($o) ? 'e' : 'o', rawurlencode($o)); ?>"><?php out($o); ?></a></td>
                        <td colspan="4"></td>
                    </tr>
                    <?php endforeach ?>

                </tbody>
                <?php endif ?>

        </table>
    </body>
</html>
