<?php
class InvoiceStyle
{
    /**
     * @var InvoiceStatus
     */
    public $status;

    public $logo;
    public $logo_width;
    public $logo_height;
    public $company_info_height;

    const MARGIN = 20;
    const INFO_CELL_HEIGHT = 5;
    const COMPANY_INFO_CELL_HEIGHT = 4;
    const INFO_CELL1_WIDTH = 285;
    const INFO_CELL2_WIDTH = 43;
    const INFO_CELL3_WIDTH = 206;
    const INFO_CELL4_WIDTH = 240;
    const INFO_CELL5_WIDTH = 0;

    const INFO_DUE_WIDTH = 70;
    const INFO_DUE_HEIGHT = 40;

    const INFO_DUE_R = 224;
    const INFO_DUE_G = 243;
    const INFO_DUE_B = 251;

    const INFO_DUE_BORDER_R = 165;
    const INFO_DUE_BORDER_G = 196;
    const INFO_DUE_BORDER_B = 251;

    const INFO_ITEMS_COL1_WIDTH = 420;
    const INFO_ITEMS_COL2_WIDTH = 112;
    const INFO_ITEMS_COL3_WIDTH = 112;
    const INFO_ITEMS_COL4_WIDTH = 112;

    const INFO_ITEMS_ROW_HEIGHT = 34;

    const INFO_ITEM_TH_R = 245;
    const INFO_ITEM_TH_G = 245;
    const INFO_ITEM_TH_B = 245;

    const ITEM_TH_BORDER_R = 221;
    const ITEM_TH_BORDER_G = 221;
    const ITEM_TH_BORDER_B = 221;

    const ITEM_ROW0_R = 251;
    const ITEM_ROW0_G = 251;
    const ITEM_ROW0_B = 251;

    const ITEM_ROW1_R = 251;
    const ITEM_ROW1_G = 251;
    const ITEM_ROW1_B = 251;

    const ITEM_BORDER_R = 221;
    const ITEM_BORDER_G = 221;
    const ITEM_BORDER_B = 221;

    const SUMMARY_WIDTH = 324;
    const SUMMARY_HEIGHT = 35;
    const SUMMARY_TOP_MARGIN = 60;

    const SUMMARY_BORDER_R = 221;
    const SUMMARY_BORDER_G = 221;
    const SUMMARY_BORDER_B = 221;

    const SUMMARY_TH_R = 245;
    const SUMMARY_TH_G = 245;
    const SUMMARY_TH_B = 245;

    const FOOTER_NOTE_R = 153;
    const FOOTER_NOTE_G = 153;
    const FOOTER_NOTE_B = 153;

    const FOOTER_BG_R = 255;
    const FOOTER_BG_G = 255;
    const FOOTER_BG_B = 0;

    const FOOTER_BG_HEIGHT = 75;

    const FOOTER_LEFT_MARGIN = 30;


    const BOX_R = 204;
    const BOX_G = 204;
    const BOX_B = 204;

    function __construct($data)
    {
        $this->status = new InvoiceStatus($data['statusText']);

        $this->logo = $data['company']['logo'];

        $this->company_info_height = 0;

        list($this->logo_width, $this->logo_height) = getimagesize($this->logo);
    }
}

class InvoiceStatus
{
    public $color;
    public $bgcolor;
    public $bordercolor;
    public $bordersize = 1;
    public $padding;

    public $text;

    function __construct($status)
    {
        switch ($status) {
            case Language::get('invoice.status_pending'):
            case Language::get('invoice.status_inactive'):
                $source = self::$Status_Inactive;
                break;

            case Language::get('invoice.status_paid'):
                $source = self::$Status_Paid;
                break;

            case Language::get('invoice.status_overdue'):
                $source = self::$Status_Overdue;
                break;
        }

        $this->padding = 10;

        $this->color = $source['color'];
        $this->bgcolor = $source['bgcolor'];
        $this->bordercolor = $source['bordercolor'];
        $this->text = $status;
    }

    private static $Status_Paid = array(
        'color' => array(0, 173, 83),
        'bgcolor' => array(229, 246, 229),
        'bordercolor' => array(187, 231, 197)
    );

    private static $Status_Inactive = array(
        'color' => array(102, 102, 102),
        'bgcolor' => array(247, 247, 247),
        'bordercolor' => array(230, 230, 230)
    );

    private static $Status_Overdue = array(
        'color' => array(255, 0, 0),
        'bgcolor' => array(255, 239, 241),
        'bordercolor' => array(255, 227, 228)
    );
}

class Units
{
    public static function Px_to_mm($px)
    {
        return $px * 0.264583333;
    }

    public static function Px_to_pt($px)
    {
        return $px * 0.755;
    }
}

class InvoicePDF extends FPDF
{
    public static $proceeds_account;


    private $InvoiceData;

    /**
     * @var InvoiceStyle
     */
    private $style;

    function __construct()
    {
        parent::FPDF('P', 'mm', 'A4');
        $this->InitializeInvoice();
    }


    function utf8($data)
    {
        if (is_object($data) || is_array($data)) {
            foreach ($data as &$value)
                $value = $this->utf8($value);
        } else $data = iconv('UTF-8', 'windows-1252', $data);

        return $data;
    }

    public function SetInvoiceData($data)
    {
        $data = $this->utf8($data);
        $this->InvoiceData = $data;
        $this->style = new InvoiceStyle($data);
    }

    public function Output($name = '', $dest = '')
    {
        parent::AddPage();

        $this->InvoiceHeader();
        $this->InvoiceItems();
        $this->InvoiceSummary();
        $this->InvoiceFooter();

        parent::AliasNbPages();

        parent::Output($name, $dest);
    }

    private function InvoiceFooter()
    {
        parent::SetTextColor(InvoiceStyle::FOOTER_NOTE_R, InvoiceStyle::FOOTER_NOTE_G, InvoiceStyle::FOOTER_NOTE_B);
        parent::SetX(0);

        parent::Cell(0, 5, '', 0, 2);

        //parent::Cell(0, 10, Language::get('invoiceView.terms'), 0, 2, 'C');

        parent::SetX(5);
        parent::MultiCell(0, 5, Language::get('invoiceView.terms'), 0, 'L');
    }

    private function NextPage()
    {
        $p = parent::PageNo();

        while (parent::PageNo() == $p)
            parent::Cell(10, 3, '', 0, 2);

        return parent::GetY();
    }

    private function InvoiceSummary()
    {
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);
        $width = Units::Px_to_mm(InvoiceStyle::SUMMARY_WIDTH);
        $height = Units::Px_to_mm(InvoiceStyle::SUMMARY_HEIGHT);
        $top_margin = Units::Px_to_mm(InvoiceStyle::SUMMARY_TOP_MARGIN);
        $spacer = '  ';

        $x = -($margin + $width);

        parent::Cell(10, $top_margin, '', 0, 2);

        parent::SetX($x);

        parent::SetFillColor(InvoiceStyle::SUMMARY_TH_R, InvoiceStyle::SUMMARY_TH_G, InvoiceStyle::SUMMARY_TH_B);
        parent::SetDrawColor(InvoiceStyle::SUMMARY_BORDER_R, InvoiceStyle::SUMMARY_BORDER_G, InvoiceStyle::SUMMARY_BORDER_B);

        if (parent::GetY() > 247)
            $this->NextPage();

        parent::Cell($width, $height, Language::get('invoiceView.summary'), 'TB', 2, 'C', true);

        $x = parent::GetX();
        $y = parent::GetY();
        $tax = $this->InvoiceData['formattedTaxRate'];

        parent::Cell(25, $height, $spacer . 'Subtotal', 'B', 2, 'L');
        parent::Cell(25, $height, $spacer . "Tax ($tax)", 'B', 2, 'L');
        parent::Cell(25, $height, $spacer . 'Total', 'B', 2, 'L');

        parent::SetY($y);
        parent::SetX($x + 25);

        $subtotal = $this->InvoiceData['formattedSubtotal'];
        $tax = $this->InvoiceData['formattedTax'];
        $total = $this->InvoiceData['formattedTotal'];

        parent::Cell($width - 25, $height, "$subtotal$spacer", 'B', 2, 'R');
        parent::Cell($width - 25, $height, "$tax$spacer", 'B', 2, 'R');
        parent::Cell($width - 25, $height, "$total$spacer", 'B', 2, 'R');
    }

    private function InvoiceItems()
    {
        parent::SetFont('Arial', '');
        parent::SetDrawColor(InvoiceStyle::ITEM_BORDER_R, InvoiceStyle::ITEM_BORDER_G, InvoiceStyle::ITEM_BORDER_B);
        parent::SetFillColor(InvoiceStyle::ITEM_ROW0_R, InvoiceStyle::ITEM_ROW0_G, InvoiceStyle::ITEM_ROW0_B);

        $items = $this->InvoiceData['invoiceItems'];
        $row0 = true;

        $len = count($items);
        $i = 0;

        foreach ($items as $item) {
            $this->InvoiceItem($item, $row0, ($i == $len - 1));
            $row0 = !$row0;
            ++$i;
        }
    }

    private function InvoiceItem($item, $row0, $is_last_item)
    {
        $wi1 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL1_WIDTH);
        $wi2 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL2_WIDTH);
        $wi3 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL3_WIDTH);
        $wi4 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL4_WIDTH);

        $cell_height = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_ROW_HEIGHT);

        $subtotal = $item['formattedSubtotal'];
        $subtotal .= '       ';

        parent::SetX(Units::Px_to_mm(20));

        $x = parent::GetX();
        $y = parent::GetY();

        if ($y > 275)
            $y = $this->NextPage();

        $cell_height = $this->ItemDescription($item['item'], 5, !$row0, $is_last_item);
        parent::SetY($y);
        parent::SetX($x + $wi1);

        $border = $is_last_item ? 'TB' : 'T';

        parent::Cell($wi2, $cell_height, $item['quantity'], $border, 0, 'C', !$row0);
        parent::Cell($wi3, $cell_height, $item['rate'] . '        ', $border, 0, 'R', !$row0);
        parent::Cell($wi4, $cell_height, $subtotal, $border, 1, 'R', !$row0);
    }

    public function ItemDescription($description, $cell_height, $row0, $is_last_item)
    {
        $w = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL1_WIDTH);

        $lines = $this->getDescriptionLines($description, $w);
        $len = count($lines);
        $cell_height = ($len == 1) ? Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_ROW_HEIGHT) : $cell_height;

        $border = $is_last_item ? 'TB' : 'T';

        if ($len > 1)
            parent::Cell($w, 2.4, '', 'T', 2, '', $row0);

        for ($i = 0; $i < $len; $i++) {
            $line = $lines[$i];
            $border = ($len == 1) ? $border : '';
            parent::Cell($w, $cell_height, "  $line", $border, 2, 'L', $row0);
        }

        if ($len > 1)
            parent::Cell($w, 2.4, '', '', 2, '', $row0);

        $cell_height = ($len == 1) ? Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_ROW_HEIGHT) : $cell_height * $len + 4.8;
        return $cell_height;
    }

    private function getDescriptionLines($description, $w)
    {
        $words = explode(' ', $description);
        $lines = array();
        $old = '';
        $tmp = array();
        $spacer = '  ';

        for ($i = 0; $i < count($words); $i++) {
            $tmp[] = $words[$i];

            if (parent::GetStringWidth($spacer . implode(' ', $tmp)) >= $w) {
                $lines[] = $old;
                $tmp = array($words[$i]);
            }

            $old = implode(' ', $tmp);
        }

        $lines[] = $old;

        return $lines;
    }

    public function InvoiceHeader()
    {
        $this->InvoiceLogo();
        $this->InvoiceStatus();
        $this->InvoiceInfo();
    }

    public function Footer()
    {
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);
        $this->SetY(-$margin * 1.8);

        parent::SetFontSize(6);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }

    private function InvoiceInfo()
    {
        parent::SetTextColor(0);

        $cell_height = InvoiceStyle::INFO_CELL_HEIGHT;
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);

        $y1 = $margin * 2 + Units::Px_to_mm($this->style->logo_height) + $this->style->company_info_height;
        $w1 = Units::Px_to_mm(InvoiceStyle::INFO_CELL1_WIDTH);
        $w2 = Units::Px_to_mm(InvoiceStyle::INFO_CELL2_WIDTH);
        $w3 = Units::Px_to_mm(InvoiceStyle::INFO_CELL3_WIDTH);
        $w4 = Units::Px_to_mm(InvoiceStyle::INFO_CELL4_WIDTH);
        $w5 = Units::Px_to_mm(InvoiceStyle::INFO_CELL5_WIDTH);

        parent::SetY($y1);
        parent::SetX($margin);
        parent::Cell(10, $cell_height, "To:");

        parent::SetY($y1 + $margin);
        parent::SetX($margin);

        parent::Cell($w1, $cell_height, $this->InvoiceData['client']['name'], 0, 2);
        parent::Cell($w1, $cell_height, $this->InvoiceData['client']['address1'], 0, 2);
        parent::Cell($w1, $cell_height, $this->InvoiceData['client']['address2'], 0, 0);

        parent::SetFont('Arial', 'B');
        parent::SetY($y1 + $margin);
        parent::SetX($margin + $w1);

        $number_short = Language::get('invoiceView.numberShort');
        $date_short = Language::get('invoiceView.dateShort');
        $status_short = Language::get('invoiceView.statusShort'); //todo. Dry
        parent::Cell($w2, $cell_height, "$number_short:", 0, 2, 'R');
        parent::Cell($w2, $cell_height, "$date_short:", 0, 2, 'R');
        parent::Cell($w2, $cell_height, "$status_short:", 0, 2, 'R');

        parent::SetFont('Arial', '');
        parent::SetY($y1 + $margin);
        parent::SetX($margin + $w1 + $w2);

        parent::Cell($w3, $cell_height, $this->InvoiceData['number'], 0, 2);
        parent::Cell($w3, $cell_height, $this->InvoiceData['dateText'], 0, 2);


        //status
        $this->SetFont('Arial');
        $this->SetFontPixelSize(13);

        $box_height = 5;
        $status = $this->style->status;
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);

        $status_width = parent::GetStringWidth($status->text) + Units::Px_to_mm($status->padding * 2);
        $label_width = parent::GetStringWidth(Language::get('invoiceView.statusShort') . ": ");
        $status_x = $margin + $status_width;
        $label_x = $status_x + $label_width + 1;

        parent::SetTextColor($status->color[0], $status->color[1], $status->color[2]);
        parent::SetFillColor($status->bgcolor[0], $status->bgcolor[1], $status->bgcolor[2]);
        parent::SetDrawColor($status->bordercolor[0], $status->bordercolor[1], $status->bordercolor[2]);


        parent::Cell($status_width, $box_height, $status->text, 1, 0, 'C', true);

        parent::SetY($y1 + $margin);
        parent::SetX($margin + $w1 + $w2 + $w3);

        //reset text color
        parent::SetTextColor(0);


        //due date
        parent::SetFont('Arial', 'B');
        parent::Cell($w2, $cell_height + 6, 'Due:', 0, 2, 'R');
        parent::SetFont('Arial', '');
        parent::SetY($y1 + $margin);
        parent::SetX($margin + 15 + $w1 + $w2 + $w3);
        $due_width = Units::Px_to_mm(InvoiceStyle::INFO_DUE_WIDTH);
        $due_height = Units::Px_to_mm(InvoiceStyle::INFO_DUE_HEIGHT);

        parent::SetFillColor(InvoiceStyle::INFO_DUE_R, InvoiceStyle::INFO_DUE_G, InvoiceStyle::INFO_DUE_B);
        parent::SetDrawColor(InvoiceStyle::INFO_DUE_BORDER_R, InvoiceStyle::INFO_DUE_BORDER_G, InvoiceStyle::INFO_DUE_BORDER_B);

        parent::Cell($due_width, $due_height, $this->InvoiceData['dueDateText'], 1, 2, 'C', true);
        //end due date

        parent::SetY($y1 + $margin);
        parent::SetX($margin + $w1 + $w2 + $w3 + $w4);


        parent::SetY($y1 + $margin + $cell_height * 4 + $margin);
        parent::SetX($margin);
        parent::SetFillColor(InvoiceStyle::INFO_ITEM_TH_R, InvoiceStyle::INFO_ITEM_TH_G, InvoiceStyle::INFO_ITEM_TH_B);

        $wi1 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL1_WIDTH);
        $wi2 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL2_WIDTH);
        $wi3 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL3_WIDTH);
        $wi4 = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_COL4_WIDTH);

        $cell_height = Units::Px_to_mm(InvoiceStyle::INFO_ITEMS_ROW_HEIGHT);
        parent::SetFont('Arial', 'B');

        parent::SetDrawColor(InvoiceStyle::ITEM_TH_BORDER_R, InvoiceStyle::ITEM_TH_BORDER_G, InvoiceStyle::ITEM_TH_BORDER_B);
//		parent::SetDrawColor(InvoiceStyle::ITEM_BORDER_R, InvoiceStyle::ITEM_BORDER_G, InvoiceStyle::ITEM_BORDER_B);

        $item_text = Language::get('invoiceView.item');
        $quantity_text = Language::get('invoiceView.quantity');
        $rate_text = Language::get('invoiceView.rate');
        $subtotal_text = Language::get('invoiceView.subtotal');

        parent::Cell($wi1, $cell_height, '  ' . $item_text , 'T', 0, 'L', true);
        parent::Cell($wi2, $cell_height, $quantity_text, 'T', 0, 'C', true);
        parent::Cell($wi2, $cell_height, $rate_text, 'T', 0, 'C', true);
        parent::Cell($wi2, $cell_height, $subtotal_text, 'T', 1, 'C', true);
    }

    private function InvoiceStatus()
    {
        $this->SetFont('Arial');
        $this->SetFontPixelSize(13);

        $box_height = 5;
        $status = $this->style->status;
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);

        $status_width = parent::GetStringWidth($status->text) + Units::Px_to_mm($status->padding * 2);
        $label_width = parent::GetStringWidth(Language::get('invoiceView.statusShort') . ": ");
        $status_x = $margin + $status_width;
        $label_x = $status_x + $label_width + 1;

        parent::SetXY(-$label_x, $margin);


        parent::SetXY(-$status_x, $margin);

    }

    private function InvoiceLogo()
    {
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);
        parent::SetXY($margin, $margin);
        $logo = $this->style->logo;
        //TODO: ver max w&h
        $this->Image($logo);

        //draw the company info

        $address1 = $this->InvoiceData['company']['address1'];
        $address2 = $this->InvoiceData['company']['address2'];
        $phone = $this->InvoiceData['company']['phone'];
        if(!$this->has_data($address1) && !$this->has_data($address2) && !$this->has_data($phone)){
            return;
        }

        $y1 = $margin + Units::Px_to_mm($this->style->logo_height);

        parent::SetFont('Arial');
        $this->SetFontPixelSize(10);
        parent::SetDrawColor(InvoiceStyle::ITEM_TH_BORDER_R, InvoiceStyle::ITEM_TH_BORDER_G, InvoiceStyle::ITEM_TH_BORDER_B);
        parent::SetY($y1);
        parent::SetX($margin);

        $cell_height = InvoiceStyle::COMPANY_INFO_CELL_HEIGHT;
        $w1 = Units::Px_to_mm(InvoiceStyle::INFO_CELL1_WIDTH);

        if($this->has_data($address1)){
            $this->style->company_info_height += $cell_height;
            parent::Cell($w1, $cell_height, $address1, 0, 2);
        }

        if($this->has_data($address2)){
            $this->style->company_info_height += $cell_height;
            parent::Cell($w1, $cell_height, $address2, 0, 2);
        }

        if($this->has_data($phone)){
            $this->style->company_info_height += $cell_height;
            parent::Cell($w1, $cell_height, $phone, 0, 0);
        }



    }

    function has_data($field = null){
        return isset($field) && $field != '';
    }

    private function SetFontPixelSize($px)
    {
        $font_size = Units::Px_to_pt($px);
        parent::SetFontSize($font_size);
    }

    private function InitializeInvoice()
    {
        $this->InitializeMargins();
    }

    private function InitializeMargins()
    {
        $margin = Units::Px_to_mm(InvoiceStyle::MARGIN);

        $this->SetAutoPageBreak(true, $margin * 1.5);
        $this->SetMargins(0, $margin, 0);
    }
}
 
