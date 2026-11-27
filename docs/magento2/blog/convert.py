#!/usr/bin/env python3
"""
Convert markdown learning-path articles to styled HTML pages.
Reads articles.json for the article list, converts each markdown file,
and outputs a complete HTML page with navigation, TOC, and AlpineCommerce styling.
"""
import os
import re
import json
import html

BASE_DIR = os.path.dirname(os.path.abspath(__file__))  # docs/magento2/blog
MAGENTO2_DIR = os.path.join(BASE_DIR, "..")  # docs/magento2/

PHASes_BADGE = {
    1: ("Phase 1: Core", "phase-tag--orange"),
    2: ("Phase 2: Frontend", "phase-tag--orange"),
    3: ("Phase 3: Extensions", "phase-tag--orange"),
    4: ("Phase 4: APIs & Admin", "phase-tag--charcoal"),
}

def md_to_html(md_text, article_num, total_articles):
    """Convert markdown text to HTML body content."""
    lines = md_text.split('\n')
    result = []
    in_code_block = False
    lang = ''
    in_table = False
    code_lines = []
    toc_entries = []
    table_header_count = 0
    paragraph_buffer = []

    def flush_paragraph():
        if paragraph_buffer:
            text = ' '.join(paragraph_buffer).strip()
            if text:
                processed = process_inline(text)
                result.append(f'<p>{processed}</p>')
            paragraph_buffer.clear()

    def process_inline(text):
        """Process inline markdown: code, links, bold, italic."""
        # Protect code spans first
        code_spans = []
        def save_code(m):
            code_spans.append(html.escape(m.group(1)))
            return f'\x00{len(code_spans)-1}\x00'
        text = re.sub(r'`([^`]+)`', save_code, text)
        # Links: [text](url)
        text = re.sub(r'\[([^\]]+)\]\(([^)]+)\)', r'<a href="\2">\1</a>', text)
        # Inline code (replaced with escaped version)
        def restore_code(m):
            return f'<code>{code_spans[int(m.group(1))]}</code>'
        text = re.sub(r'\x00(\d+)\x00', restore_code, text)
        # Bold
        text = re.sub(r'\*\*([^*]+)\*\*', r'<strong>\1</strong>', text)
        # Italic
        text = re.sub(r'(?<!\*)\*([^*]+)\*(?!\*)', r'<em>\1</em>', text)
        return text

    def render_toc():
        if not toc_entries:
            return ''
        items = []
        for anchor, text, level in toc_entries:
            indent = max(0, level - 2) * 0.5
            items.append(
                f'  <li style="margin-left: {indent}rem"><a href="#anchor-{anchor}" class="toc-link">{html.escape(text)}</a></li>'
            )
        return f'''<nav class="toc-nav mb-8">
<ul class="space-y-1">
{chr(10).join(items)}
</ul>
</nav>'''

    lines_iter = iter(enumerate(lines, 1))

    for line_num, raw_line in lines_iter:
        line = raw_line.rstrip()

        # Code block fence
        if line.strip().startswith('```'):
            flush_paragraph()
            if not in_code_block:
                in_code_block = True
                lang = line.strip()[3:].strip()
                code_lines = []
            else:
                code_html = '\n'.join(html.escape(cl) for cl in code_lines)
                lang_class = f' language-{lang}' if lang else ''
                result.append(f'<pre><code class="language-{lang}"{lang_class}>{code_html}</code></pre>')
                in_code_block = False
                lang = ''
            continue

        if in_code_block:
            code_lines.append(line)
            continue

        # Horizontal rule
        if line.strip() == '---' or re.match(r'^---+\s*$', line):
            flush_paragraph()
            result.append('<hr>')
            continue

        # Headers
        header_match = re.match(r'^(#{1,6})\s*(.+)$', line)
        if header_match:
            flush_paragraph()
            level = len(header_match.group(1))
            text = process_inline(header_match.group(2).strip())
            anchor = re.sub(r'[^\w\s-]', '', header_match.group(2).strip().lower()).replace(' ', '-')
            toc_entries.append((anchor, header_match.group(2).strip(), level))
            result.append(f'<h{level} id="anchor-{anchor}">{text}</h{level}>')
            continue

        # Blockquote
        if line.strip().startswith('> '):
            flush_paragraph()
            content = line.strip()[2:].strip()
            processed = process_inline(content)
            result.append(f'<blockquote>{processed}</blockquote>')
            continue

        # Table row
        if '|' in line and not line.strip().startswith('|') is False:
            # Potential table row
            cells = [c.strip() for c in line.split('|')[1:-1]]
            # Check if this is a separator row
            if all(re.match(r'^[:\- ]+$', c) for c in cells if c):
                in_table = True
                table_header_count = len(result) + 1
                continue
            if in_table:
                # This is a data row
                processed_cells = [process_inline(c) for c in cells]
                if len(result) == 0 or not result[-1].startswith('<tr'):
                    result.append('<table class="table"><thead><tr>' + ''.join(f'<th>{process_inline(c)}</th>' for c in cells) + '</tr></thead><tbody>')
                    in_table = False
                else:
                    result.append('<tr>' + ''.join(f'<td>{c}</td>' for c in processed_cells) + '</tr>')
                continue

        # List items
        list_match = re.match(r'^([-*]\s+(.+))$', line)
        if list_match:
            flush_paragraph()
            item_text = process_inline(list_match.group(2).strip())
            if not result or not result[-1].startswith('<ul'):
                result.append('<ul class="list-disc pl-5 mb-4">')
            result.append(f'<li>{item_text}</li>')
            continue

        # Close list if next line isn't a list
        if result and result[-1].startswith('<ul') or (result and result[-1].startswith('<li>')):
            pass  # will be closed when we hit a non-list line

        # Regular text (paragraph)
        if line.strip() and not line.strip().startswith('|'):
            paragraph_buffer.append(line.strip())
        else:
            flush_paragraph()

    flush_paragraph()

    # Close any open tags
    if result and result[-1].startswith('<ul') or (result and result[-1].startswith('<li>')):
        if not result[-1] == '</ul>':
            # Insert </ul> after the last <li>
            pass

    # Build TOC
    toc_html = render_toc()

    # Join result, closing any open <ul>
    body = []
    for i, chunk in enumerate(result):
        body.append(chunk)
        # Close <ul> if next item is not a <li> and current is <li>
        if chunk.startswith('<li>') and i + 1 < len(result) and not result[i+1].startswith('<li>'):
            body.append('</ul>')
    # If last element is <li>, close the <ul>
    if result and result[-1].startswith('<li>'):
        body.append('</ul>')

    return toc_html, '\n'.join(body)


def get_meta(md_text):
    """Extract metadata from the markdown front-matter (Series, Previous, Next, Deep dive)."""
    lines = md_text.split('\n')
    meta = {
        'series': '',
        'previous': '',
        'previous_url': '',
        'next': '',
        'next_url': '',
        'deep_dive': '',
        'deep_dive_url': '',
    }
    for line in lines:
        if not line.strip().startswith('>'):
            break
        content = line.strip()[1:].strip()
        if content.startswith('**Series**:'):
            meta['series'] = content.replace('**Series**:', '').strip()
        match = re.search(r'\*\*Previous\*\*:\s+(\[([^\]]+)\]\(([^)]+)\)|[^,\n]+)', content)
        if '**Previous**:' in content:
            m = re.search(r'\*\*Previous\*\*:\s+(\[([^\]]+)\]\(([^)]+)\))', content)
            if m:
                meta['previous'] = m.group(2)
                meta['previous_url'] = m.group(3)
            elif '—' in content or 'first' in content.lower():
                pass  # first article
        if '**Next**:' in content:
            m = re.search(r'\*\*Next\*\*:\s+(\[([^\]]+)\]\(([^)]+)\))', content)
            if m:
                meta['next'] = m.group(2)
                meta['next_url'] = m.group(3)
        if '**Deep dive**:' in content:
            m = re.search(r'\*\*Deep dive\*\*:\s+(\[([^\]]+)\]\(([^)]+)\))', content)
            if m:
                meta['deep_dive'] = m.group(2)
                meta['deep_dive_url'] = m.group(3)
    return meta


def generate_article_html(article, article_num, total_articles, all_articles):
    md_path = os.path.join(MAGENTO2_DIR, article['file'])
    with open(md_path, 'r', encoding='utf-8') as f:
        md_text = f.read()

    meta = get_meta(md_text)

    # Remove the front-matter block from markdown content
    md_body = md_text
    front_matter_end = None
    for i, line in enumerate(md_text.split('\n')):
        if not line.strip().startswith('>') and i > 0:
            front_matter_end = i
            break
    if front_matter_end:
        md_body = '\n'.join(md_text.split('\n')[front_matter_end:])

    # Skip leading --- separator
    md_body = re.sub(r'^---\s*\n', '', md_body.strip())

    toc_html, body_html = md_to_html(md_body, article_num, total_articles)

    prev_html = ''
    if meta['previous_url'] and meta['previous']:
        prev_html = f'''<a href="{meta['previous_url']}" class="nav-prev">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
  {meta['previous']}
</a>'''
    elif article_num > 1:
        prev_art = all_articles[article_num - 2]
        prev_html = f'''<a href="{prev_art['html']}" class="nav-prev">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
  {prev_art['title']}
</a>'''

    next_html = ''
    if meta['next_url'] and meta['next']:
        next_html = f'''<a href="{meta['next_url']}" class="nav-next">
  {meta['next']}
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>'''
    elif article_num < total_articles:
        next_art = all_articles[article_num]
        next_html = f'''<a href="{next_art['html']}" class="nav-next">
  {next_art['title']}
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>'''

    phase_name, phase_tag_class = PHASes_BADGE.get(article['phase'], ("Phase", "phase-tag--orange"))

    deep_dive_html = ''
    if meta['deep_dive_url']:
        # Adjust the deep dive link to point relative to the articles directory
        deep_dive_href = meta['deep_dive_url'].replace('../', '../../')
        deep_dive_html = f'''<div class="deep-dive-link">
  Deep dive: <a href="{deep_dive_href}">{meta['deep_dive']}</a>
</div>'''

    prev_article_text = meta.get('previous', '') if meta.get('previous') else ''
    next_article_text = meta.get('next', '') if meta.get('next') else ''

    if meta.get('previous') == '— (first article)' and article_num > 1:
        prev_article_text = all_articles[article_num - 2]['title']
    if not next_html and article_num < total_articles:
        next_article_text = all_articles[article_num]['title']

    html_page = f'''<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{html.escape(article['title'])} — AlpineCommerce Dev Docs</title>
  <meta name="description" content="{html.escape(article['title'])} — Part of the Magento 2 learning path.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="../assets/app.js"></script>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body {{ font-family: 'Inter Tight', system-ui, sans-serif; }}
    code {{ font-family: 'JetBrains Mono', monospace; }}
    [x-cloak] {{ display: none !important; }}
    .table-container {{ overflow-x: auto; }}
    table {{ min-width: 40rem; }}
  </style>
</head>
<body class="font-sans bg-off-white text-charcoal">
  <header class="site-header">
    <div class="header-container">
      <a href="../index.html" class="site-logo">
        <svg viewBox="0 0 40 46" fill="none" xmlns="http://www.w3.org/2000/svg">
          <polygon points="20,2 38,11 38,29 20,38 2,29 2,11" stroke="#D4551A" stroke-width="2" fill="none"/>
          <polygon points="20,8 32,15 32,25 20,32 8,25 8,15" stroke="#F1BC1B" stroke-width="1.5" fill="none"/>
          <polygon points="20,14 26,18 26,22 20,26 14,22 14,18" fill="#D4551A"/>
        </svg>
        <span>AlpineCommerce Dev Docs</span>
      </a>
      <a href="../index.html" class="text-sm font-medium text-charcoal hover:text-orange">Learning Path</a>
    </div>
  </header>

  <main class="max-width px-4 sm:px-6 lg:px-8 py-4">
    <div class="lg-grid lg-grid-2 gap-8">
      <article class="lg:col-span-3">
        <div class="article-container">
          <div class="mb-6">
            <span class="phase-tag {phase_tag_class}">{html.escape(phase_name)}</span>
            <span class="article-meta ml-2">Article {article_num} of {total_articles} • ~{article['time']}</span>
          </div>

          <header class="article-header">
            <h1 class="article-title">{html.escape(article['title'])}</h1>
            {deep_dive_html}
          </header>

          <div class="lg-flex gap-12">
            <div class="article-content max-w-4xl">
              {body_html}
            </div>
          </div>

          <nav class="article-nav">
            {prev_html}
            {next_html}
          </nav>
        </div>
      </article>

      <aside class="toc">
        {toc_html}
      </aside>
    </div>
  </main>

  <button id="backToTopBtn" class="back-to-top" aria-label="Back to top">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="square" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
  </button>

  <footer class="site-footer">
    <div class="footer-container">
      <p class="text-sm text-charcoal-200">All documentation open-source.</p>
    </div>
  </footer>
</body>
</html>'''

    out_path = os.path.join(BASE_DIR, article['html'])
    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write(html_page)
    print(f"Generated: {out_path}")


if __name__ == '__main__':
    json_path = os.path.join(BASE_DIR, 'articles.json')
    with open(json_path, 'r', encoding='utf-8') as f:
        articles = json.load(f)['articles']

    for i, article in enumerate(articles):
        generate_article_html(article, i + 1, len(articles), articles)

    print(f"\nDone! Generated {len(articles)} article HTML files.")
