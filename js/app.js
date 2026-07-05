document.addEventListener('DOMContentLoaded', () => {
  const btn = document.querySelector('.mobile-menu-btn');
  const links = document.getElementById('navLinks');
  if (btn && links) btn.addEventListener('click', () => links.classList.toggle('active'));
});
function runCode() {
  const code = document.getElementById('editor').value;
  const out = document.getElementById('output');
  out.innerHTML = '<span style="color:var(--text-muted)">Compiling...</span>';
  setTimeout(() => {
    const lines = [];
    if (code.includes('println')) {
      const m = code.match(/println\(["']([^"']+)["']\)/g);
      if (m) m.forEach(x => lines.push(x.match(/["']([^"']+)["']/)[1]));
    }
    if (code.includes('sort')) { lines.push('1','1','2','3','4','5','6','9'); }
    if (lines.length === 0) lines.push('// Demo: Helion code would run here');
    out.innerHTML = lines.map(l => '<div>' + l + '</div>').join('');
  }, 800);
}
function shareCode() {
  const code = document.getElementById('editor').value;
  const url = location.origin + location.pathname + '?code=' + btoa(encodeURIComponent(code));
  navigator.clipboard.writeText(url).then(() => alert('Link copied!'));
}
function downloadCode() {
  const blob = new Blob([document.getElementById('editor').value], {type:'text/plain'});
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'program.hel'; a.click();
}
