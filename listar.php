<?php
include "conexao.php";

$sql = "SELECT * FROM usuarios";
$resultado = $conexao->query($sql);

echo "<h1>Usuários cadastrados</h1>";

if ($resultado->num_rows > 0) {
    while ($usuario = $resultado->fetch_assoc()) {
        echo "ID: " . $usuario["id_usuario"] . "<br>";
        echo "Nome: " . $usuario["nome"] . "<br>";
        echo "E-mail: " . $usuario["email"] . "<br>";
        echo "Tipo: " . $usuario["tipo"] . "<br>";
        echo "<hr>";
    }

} else {
    echo "Nenhum usuário cadastrado.";
}
?>