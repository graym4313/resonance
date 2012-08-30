<table class="programme">
    <tr><td class="time"><?= date('H:i', $item['start']); ?></td><td class="title"><?= htmlspecialchars($item['title']); ?></td></tr>
    <tr><td></td><td class="content"><?= $item['content']; ?></td></tr>
    <tr><td></td><td><ol>
      <?php if (!empty($item['files'])) foreach ($item['files'] as $file): ?>
         <li><a class="track" href="<?= htmlspecialchars($file['file']); ?>"><?= htmlspecialchars($file['title'] ? $file['title'] : date('j F H:i', $file['date'])); ?></a> <? if (!isset($file['title'])) print $this->format_file_size($file['size']); ?></li>
      <?php endforeach; ?>
      </ol></td></tr>
</table>

