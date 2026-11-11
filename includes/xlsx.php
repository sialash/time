<?php
/**
 * تولید فایل Excel (xlsx) به‌صورت خالص در PHP — بدون نیاز به کتابخانه جانبی
 * فقط به افزونه Zip نیاز دارد؛ در غیر این صورت خروجی CSV جایگزین می‌شود.
 */
class MiniXlsx {
    private $sheets = [];

    /**
     * افزودن شیت
     * @param string $name   نام شیت
     * @param array  $rows   ردیف‌ها؛ هر سلول: رشته (متن) | عدد | آرایه ['v'=>..., 'num'=>bool, 'style'=>'header|total']
     * @param array  $widths عرض ستون‌ها
     */
    public function addSheet($name, array $rows, array $widths = []) {
        $this->sheets[] = ['name' => $name, 'rows' => $rows, 'widths' => $widths];
    }

    private function esc($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }

    private function styleIdx($style) {
        switch ($style) {
            case 'header': return 2;
            case 'total':  return 1;
            case 'filled': return 3;
            default:       return 0;
        }
    }

    private function cellRef($rowIdx, $colIdx) {
        $col = '';
        $n = $colIdx;
        while ($n >= 0) {
            $col = chr(65 + ($n % 26)) . $col;
            $n = intdiv($n, 26) - 1;
        }
        return $col . ($rowIdx + 1);
    }

    private function sheetXml($sheet) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<sheetViews><sheetView rightToLeft="1" workbookViewId="0"><selection activeCell="A1" sqref="A1"/></sheetView></sheetViews>'
             . '<sheetFormatPr defaultRowHeight="20"/>';
        if (!empty($sheet['widths'])) {
            $xml .= '<cols>';
            foreach ($sheet['widths'] as $i => $w) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float)$w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($sheet['rows'] as $r => $cells) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach ($cells as $c => $cell) {
                $ref = $this->cellRef($r, $c);
                if (is_array($cell)) {
                    $style = isset($cell['style']) ? $cell['style'] : null;
                    $num   = !empty($cell['num']);
                    $val   = isset($cell['v']) ? $cell['v'] : '';
                } else {
                    $style = null;
                    $num   = !is_string($cell) && is_numeric($cell);
                    $val   = $cell;
                }
                $s = $this->styleIdx($style);
                $sAttr = $s ? ' s="' . $s . '"' : '';
                if ($num && is_numeric($val)) {
                    $xml .= '<c r="' . $ref . '"' . $sAttr . '><v>' . (0 + $val) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t xml:space="preserve">' . $this->esc($val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /** ساخت بسته xlsx و بازگرداندن باینری؛ اگر ZipArchive نبود false */
    public function build() {
        if (!class_exists('ZipArchive') || count($this->sheets) === 0) return false;
        $n = count($this->sheets);

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $wbSheets = '';
        $wbRels   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $sh) {
            $id = $i + 1;
            $wbSheets .= '<sheet name="' . $this->esc($sh['name']) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
            $wbRels   .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        }
        $wbRels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $wbSheets . '</sheets></workbook>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Tahoma"/></font><font><b/><sz val="11"/><name val="Tahoma"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCE6F1"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="2" borderId="0" xfId="0" applyFill="1"/>'
            . '</cellXfs></styleSheet>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            return false;
        }
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/styles.xml', $styles);
        foreach ($this->sheets as $i => $sh) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sh));
        }
        $zip->close();
        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }
}
