<?php
declare(strict_types=1);
$chatbotRoot = $chatbotPrefix ?? '';
?>
<link rel="stylesheet" href="<?= e($chatbotRoot) ?>assets/css/chatbot.css?v=<?= e(filemtime(__DIR__.'/../../public/assets/css/chatbot.css')) ?>">
<div class="tattoo-oracle" data-chatbot
     data-history="<?= e($chatbotRoot) ?>chatbot/historial.php"
     data-message="<?= e($chatbotRoot) ?>chatbot/mensaje.php"
     data-feedback="<?= e($chatbotRoot) ?>chatbot/feedback.php"
     data-base="<?= e($chatbotRoot) ?>"
     data-csrf="<?= e(csrf()) ?>">
    <button class="tattoo-oracle__summon" type="button" aria-expanded="false" aria-controls="tattoo-oracle-panel">
        <span class="tattoo-oracle__sigil" aria-hidden="true"><i></i><b>✦</b></span>
        <span><small>CONSULTA EL</small><strong>ORÁCULO</strong></span>
    </button>

    <section class="tattoo-oracle__panel" id="tattoo-oracle-panel" aria-label="Chatbot de Tinta Viva" hidden>
        <header class="tattoo-oracle__header">
            <div class="tattoo-oracle__moon" aria-hidden="true"><span>TV</span></div>
            <div><small>ASISTENTE DEL ESTUDIO</small><h2>El Oráculo de Tinta</h2><p><i></i> En línea · respuestas desde el archivo</p></div>
            <button type="button" data-chatbot-close aria-label="Cerrar chatbot">×</button>
        </header>
        <div class="tattoo-oracle__warning">No reemplaza consejo médico. Para urgencias, consulta a un profesional de salud.</div>
        <div class="tattoo-oracle__messages" data-chatbot-messages aria-live="polite">
            <div class="tattoo-oracle__loading"><i></i><i></i><i></i><span>Abriendo el archivo…</span></div>
        </div>
        <div class="tattoo-oracle__prompts" data-chatbot-prompts>
            <button type="button" data-prompt="¿Cómo solicito una cotización?">Cotizar una idea</button>
            <button type="button" data-prompt="¿Cómo debo cuidar mi tatuaje?">Cuidados</button>
            <button type="button" data-prompt="Quiero conocer a los artistas">Artistas</button>
            <button type="button" data-prompt="Quiero hablar con una persona del estudio">Hablar con el estudio</button>
        </div>
        <form class="tattoo-oracle__form" data-chatbot-form>
            <label class="sr-only" for="tattoo-oracle-input">Escribe tu pregunta</label>
            <textarea id="tattoo-oracle-input" name="mensaje" maxlength="700" rows="1" placeholder="Escribe tu pregunta…" required></textarea>
            <button type="submit" aria-label="Enviar mensaje"><span>Enviar</span><b aria-hidden="true">↗</b></button>
        </form>
        <p class="tattoo-oracle__status" data-chatbot-status role="status"></p>
        <footer><span>✦</span> BASE DE CONOCIMIENTO TINTA VIVA <span>✦</span></footer>
    </section>
</div>
<script src="<?= e($chatbotRoot) ?>assets/js/chatbot.js?v=<?= e(filemtime(__DIR__.'/../../public/assets/js/chatbot.js')) ?>" defer></script>
