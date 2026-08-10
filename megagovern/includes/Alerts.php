<?php
/**
 * Regulatory Alerts — V1.0.4
 * 
 * Bundled regulatory data updated with each plugin release.
 * Covers 15 jurisdictions and international frameworks.
 *
 * CHANGELOG v1.0.4:
 * - UPDATED: All regulation dates and statuses refreshed for 2024-2026
 * - ADDED: 10 new jurisdictions (UK, Canada, Brazil, Japan, South Korea,
 *          Australia, G7, UN, Utah, California AB 2013)
 * - ADDED: compliance_deadline field where applicable
 * - FIXED: clear_all_read_status() now requires manage_options capability
 *
 * @package MegaGovern
 * @since   1.0.4
 */
namespace MegaGovern;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Alerts {
    /**
     * Constructor — registers AJAX handlers only.
     */
    public function __construct() {
        add_action( 'wp_ajax_megagovern_mark_alert_read', [ $this, 'ajax_mark_read' ] );
        add_action( 'wp_ajax_megagovern_mark_all_alerts_read', [ $this, 'ajax_mark_all_read' ] );
    }
    /**
     * Get bundled regulatory data.
     *
     * Sources updated: January 2026.
     *
     * @return array
     */
    private static function get_bundled_alerts(): array {
        return [
            // ═══════════════════════════════════════
            // EUROPEAN UNION
            // ═══════════════════════════════════════
            [
                'id'                     => 'eu-ai-act-general',
                'title'                  => __( 'EU AI Act — General Provisions (Enacted)', 'megagovern' ),
                'description'            => __( 'The EU AI Act entered into force on August 1, 2024. General provisions and prohibited practices apply from February 2, 2025. Transparency obligations for GPAI models apply from August 2, 2025. High-risk AI system obligations phase in through August 2, 2027.', 'megagovern' ),
                'jurisdiction'           => 'eu',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2024-08-01',
                'compliance_deadline'    => '2027-08-02',
                'published_at'           => '2024-07-12',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video', 'deepfake' ],
                'link'                   => 'https://artificialintelligenceact.eu/',
            ],
            [
                'id'                     => 'eu-ai-act-transparency',
                'title'                  => __( 'EU AI Act — Article 50 Transparency (Effective August 2026)', 'megagovern' ),
                'description'            => __( 'Article 50 requires clear labeling of AI-generated content including text, images, audio, and video. Deepfakes must be explicitly marked. Limited exemptions for artistic and satirical content. Applies to all content accessible in the EU.', 'megagovern' ),
                'jurisdiction'           => 'eu',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2026-08-02',
                'compliance_deadline'    => '2026-08-02',
                'published_at'           => '2024-07-12',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video', 'deepfake' ],
                'link'                   => 'https://artificialintelligenceact.eu/article/50/',
            ],
            // ═══════════════════════════════════════
            // UNITED STATES — FEDERAL
            // ═══════════════════════════════════════
            [
                'id'                     => 'us-ai-executive-order',
                'title'                  => __( 'US Executive Order 14110 — Safe & Secure AI', 'megagovern' ),
                'description'            => __( 'Executive Order on Safe, Secure, and Trustworthy Development and Use of AI (October 2023). Requires developers of powerful AI systems to share safety test results. NIST developing AI content authentication and watermarking standards.', 'megagovern' ),
                'jurisdiction'           => 'us',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2023-10-30',
                'compliance_deadline'    => '2025-07-01',
                'published_at'           => '2023-10-30',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.whitehouse.gov/briefing-room/presidential-actions/2023/10/30/executive-order-on-the-safe-secure-and-trustworthy-development-and-use-of-artificial-intelligence/',
            ],
            [
                'id'                     => 'us-no-ai-fraud-act',
                'title'                  => __( 'US NO AI FRAUD Act (Proposed)', 'megagovern' ),
                'description'            => __( 'Federal bill proposed in January 2024 to establish intellectual property rights over voice and likeness against unauthorized AI-generated replicas.', 'megagovern' ),
                'jurisdiction'           => 'us',
                'severity'               => 'medium',
                'regulatory_status'      => 'proposed',
                'date'                   => '2024-01-10',
                'compliance_deadline'    => '',
                'published_at'           => '2024-01-10',
                'affected_content_types' => [ 'image', 'audio', 'video', 'deepfake' ],
                'link'                   => 'https://www.congress.gov/bill/118th-congress/house-bill/6943',
            ],
            // ═══════════════════════════════════════
            // UNITED STATES — CALIFORNIA
            // ═══════════════════════════════════════
            [
                'id'                     => 'ca-sb942',
                'title'                  => __( 'California SB 942 — AI Transparency Act (Signed)', 'megagovern' ),
                'description'            => __( 'Signed September 2024. Requires providers of generative AI systems to include provenance disclosures in AI-generated content. Mandates visible and machine-readable labels for AI-generated images, video, and audio.', 'megagovern' ),
                'jurisdiction'           => 'us',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2024-09-19',
                'compliance_deadline'    => '2026-01-01',
                'published_at'           => '2024-09-19',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://leginfo.legislature.ca.gov/faces/billNavClient.xhtml?bill_id=202320240SB942',
            ],
            [
                'id'                     => 'ca-ab2013',
                'title'                  => __( 'California AB 2013 — AI Training Data Disclosure (Signed)', 'megagovern' ),
                'description'            => __( 'Signed September 2024. Requires developers of generative AI systems to publicly disclose information about the datasets used to train their models, including data sources and copyright status.', 'megagovern' ),
                'jurisdiction'           => 'us',
                'severity'               => 'medium',
                'regulatory_status'      => 'enacted',
                'date'                   => '2024-09-19',
                'compliance_deadline'    => '2026-01-01',
                'published_at'           => '2024-09-19',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://leginfo.legislature.ca.gov/faces/billNavClient.xhtml?bill_id=202320240AB2013',
            ],
            // ═══════════════════════════════════════
            // UNITED STATES — COLORADO
            // ═══════════════════════════════════════
            [
                'id'                     => 'co-ai-act',
                'title'                  => __( 'Colorado AI Act (SB 24-205) — Effective February 2026', 'megagovern' ),
                'description'            => __( 'Signed May 2024. First comprehensive state AI law in the US. Requires developers and deployers of high-risk AI systems to use reasonable care to protect consumers from algorithmic discrimination. Effective February 1, 2026.', 'megagovern' ),
                'jurisdiction'           => 'us',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2024-05-17',
                'compliance_deadline'    => '2026-02-01',
                'published_at'           => '2024-05-17',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://leg.colorado.gov/bills/sb24-205',
            ],
            // ═══════════════════════════════════════
            // UNITED STATES — UTAH
            // ═══════════════════════════════════════
            [
                'id'                     => 'ut-ai-policy',
                'title'                  => __( 'Utah AI Policy Act (SB 149) — Enacted', 'megagovern' ),
                'description'            => __( 'Enacted March 2024. First state AI law in the US to take effect. Requires disclosure when consumers interact with generative AI in regulated occupations (healthcare, legal, financial). Effective May 1, 2024.', 'megagovern' ),
                'jurisdiction'           => 'us',
                'severity'               => 'medium',
                'regulatory_status'      => 'enacted',
                'date'                   => '2024-03-13',
                'compliance_deadline'    => '2024-05-01',
                'published_at'           => '2024-03-13',
                'affected_content_types' => [ 'text' ],
                'link'                   => 'https://le.utah.gov/~2024/bills/static/SB0149.html',
            ],
            // ═══════════════════════════════════════
            // CHINA
            // ═══════════════════════════════════════
            [
                'id'                     => 'china-deep-synthesis',
                'title'                  => __( 'China — Deep Synthesis Provisions (Enacted)', 'megagovern' ),
                'description'            => __( 'Effective January 10, 2023. Requires AI-generated content to be watermarked and clearly labeled. Deep synthesis service providers must obtain user consent, verify identities, and maintain audit logs. Applies to content accessible within China.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2023-01-10',
                'compliance_deadline'    => '2023-01-10',
                'published_at'           => '2022-12-11',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video', 'deepfake' ],
                'link'                   => 'https://www.chinalawtranslate.com/en/deep-synthesis/',
            ],
            [
                'id'                     => 'china-generative-ai',
                'title'                  => __( 'China — Generative AI Measures (Enacted)', 'megagovern' ),
                'description'            => __( 'Effective August 15, 2023. Regulates generative AI services available to the Chinese public. Requires content filtering, labeling of AI-generated output, protection of user data, and adherence to socialist core values.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2023-08-15',
                'compliance_deadline'    => '2023-08-15',
                'published_at'           => '2023-07-13',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.chinalawtranslate.com/en/generative-ai-measures/',
            ],
            // ═══════════════════════════════════════
            // UNITED KINGDOM
            // ═══════════════════════════════════════
            [
                'id'                     => 'uk-ai-regulation',
                'title'                  => __( 'UK — AI Regulation Framework (In Development)', 'megagovern' ),
                'description'            => __( 'The UK follows a principles-based, sector-led approach via existing regulators (ICO, CMA, Ofcom, FCA). The AI Safety Institute was launched in November 2023. No comprehensive AI legislation yet; the government is consulting on targeted requirements for frontier AI.', 'megagovern' ),
                'jurisdiction'           => 'eu',
                'severity'               => 'medium',
                'regulatory_status'      => 'framework',
                'date'                   => '2023-03-29',
                'compliance_deadline'    => '',
                'published_at'           => '2023-03-29',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.gov.uk/government/publications/ai-regulation-a-pro-innovation-approach',
            ],
            // ═══════════════════════════════════════
            // CANADA
            // ═══════════════════════════════════════
            [
                'id'                     => 'ca-aida',
                'title'                  => __( 'Canada — AIDA (Bill C-27) — Proposed', 'megagovern' ),
                'description'            => __( 'The Artificial Intelligence and Data Act (AIDA) is part of Bill C-27, currently in parliamentary committee. Would establish requirements for high-impact AI systems including transparency, risk assessment, and human oversight. Expected passage: 2025-2026.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'medium',
                'regulatory_status'      => 'proposed',
                'date'                   => '2024-12-01',
                'compliance_deadline'    => '',
                'published_at'           => '2023-09-26',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.parl.ca/legisinfo/en/bill/44-1/c-27',
            ],
            // ═══════════════════════════════════════
            // BRAZIL
            // ═══════════════════════════════════════
            [
                'id'                     => 'br-ai-bill',
                'title'                  => __( 'Brazil — AI Bill (PL 2338/2023) — In Progress', 'megagovern' ),
                'description'            => __( 'Comprehensive AI regulation under debate in the Brazilian Senate. Would establish risk-based classification of AI systems, require transparency for AI-generated content, and mandate human oversight for high-risk applications.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'medium',
                'regulatory_status'      => 'proposed',
                'date'                   => '2024-12-10',
                'compliance_deadline'    => '',
                'published_at'           => '2024-12-10',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www25.senado.leg.br/web/atividade/materias/-/materia/157233',
            ],
            // ═══════════════════════════════════════
            // JAPAN
            // ═══════════════════════════════════════
            [
                'id'                     => 'jp-ai-guidelines',
                'title'                  => __( 'Japan — AI Business Guidelines (Published 2024)', 'megagovern' ),
                'description'            => __( 'Japan\'s Ministry of Economy, Trade and Industry (METI) published updated AI Guidelines in April 2024. Currently voluntary but signals future regulatory direction. Focuses on transparency, fairness, and human-centric AI.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'low',
                'regulatory_status'      => 'voluntary',
                'date'                   => '2024-04-01',
                'compliance_deadline'    => '',
                'published_at'           => '2024-04-01',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.meti.go.jp/english/press/2024/0419_001.html',
            ],
            // ═══════════════════════════════════════
            // SOUTH KOREA
            // ═══════════════════════════════════════
            [
                'id'                     => 'kr-ai-act',
                'title'                  => __( 'South Korea — AI Basic Act (Proposed 2024)', 'megagovern' ),
                'description'            => __( 'Proposed comprehensive AI legislation introduced in the National Assembly. Would establish a legal framework for AI development, require high-risk AI transparency, and mandate labeling of AI-generated content.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'medium',
                'regulatory_status'      => 'proposed',
                'date'                   => '2024-06-01',
                'compliance_deadline'    => '',
                'published_at'           => '2024-06-01',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.msit.go.kr/eng/index.do',
            ],
            // ═══════════════════════════════════════
            // AUSTRALIA
            // ═══════════════════════════════════════
            [
                'id'                     => 'au-ai-ethics',
                'title'                  => __( 'Australia — AI Ethics Framework (Voluntary)', 'megagovern' ),
                'description'            => __( 'Australia\'s voluntary AI Ethics Framework (updated 2024) outlines 8 principles including transparency, explainability, and accountability. The government is consulting on mandatory guardrails for high-risk AI.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'low',
                'regulatory_status'      => 'voluntary',
                'date'                   => '2024-01-01',
                'compliance_deadline'    => '',
                'published_at'           => '2024-01-01',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://www.industry.gov.au/publications/australias-artificial-intelligence-ethics-framework',
            ],
            // ═══════════════════════════════════════
            // INTERNATIONAL
            // ═══════════════════════════════════════
            [
                'id'                     => 'g7-ai-code',
                'title'                  => __( 'G7 — International AI Code of Conduct (Agreed 2023)', 'megagovern' ),
                'description'            => __( 'Agreed October 30, 2023 by G7 leaders. Establishes 11 guiding principles for AI developers including transparency, accountability, and content authentication. While non-binding, it signals international regulatory direction.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'medium',
                'regulatory_status'      => 'framework',
                'date'                   => '2023-10-30',
                'compliance_deadline'    => '',
                'published_at'           => '2023-10-30',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://digital-strategy.ec.europa.eu/en/library/hiroshima-process-international-code-conduct-advanced-ai-systems',
            ],
            [
                'id'                     => 'un-ai-resolution',
                'title'                  => __( 'UN — General Assembly AI Resolution (Adopted March 2024)', 'megagovern' ),
                'description'            => __( 'Adopted unanimously March 21, 2024. First-ever standalone UN General Assembly resolution on AI. Calls for safe, secure, and trustworthy AI systems that respect human rights. Encourages member states to develop regulatory frameworks.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'medium',
                'regulatory_status'      => 'framework',
                'date'                   => '2024-03-21',
                'compliance_deadline'    => '',
                'published_at'           => '2024-03-21',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video' ],
                'link'                   => 'https://news.un.org/en/story/2024/03/1147826',
            ],
            [
                'id'                     => 'eu-council-of-europe-ai',
                'title'                  => __( 'Council of Europe — AI Treaty (Opened for Signature 2024)', 'megagovern' ),
                'description'            => __( 'The Framework Convention on AI and Human Rights, Democracy, and the Rule of Law opened for signature September 2024. First legally binding international treaty on AI. Signed by EU, US, UK, and others. Requires transparency, oversight, and accountability.', 'megagovern' ),
                'jurisdiction'           => 'global',
                'severity'               => 'high',
                'regulatory_status'      => 'enacted',
                'date'                   => '2024-09-05',
                'compliance_deadline'    => '2026-09-05',
                'published_at'           => '2024-09-05',
                'affected_content_types' => [ 'text', 'image', 'audio', 'video', 'deepfake' ],
                'link'                   => 'https://www.coe.int/en/web/artificial-intelligence/the-framework-convention-on-artificial-intelligence',
            ],
        ];
    }
    /**
     * Get cached alerts.
     * Free: Global jurisdiction only.
     * Pro/Agency: All jurisdictions + filtering.
     *
     * @param string $jurisdiction Filter by jurisdiction.
     * @return array
     */
    public static function get_alerts( string $jurisdiction = 'all' ): array {
        $alerts = self::get_bundled_alerts();
        // Free plan — only Global alerts.
        if ( class_exists( '\MegaGovern\License' ) && License::is_free() ) {
            $alerts = array_filter( $alerts, function( $alert ) {
                return 'global' === ( $alert['jurisdiction'] ?? 'global' );
            } );
            return array_values( $alerts );
        }
        if ( 'all' === $jurisdiction ) {
            return $alerts;
        }
        $filtered = array_filter( $alerts, function( $alert ) use ( $jurisdiction ) {
            return $alert['jurisdiction'] === $jurisdiction || $alert['jurisdiction'] === 'global';
        } );
        return array_values( $filtered );
    }
    /**
     * Get alert by ID.
     *
     * @param string $alert_id Alert ID.
     * @return array|null
     */
    public static function get_alert( string $alert_id ): ?array {
        $alerts = self::get_bundled_alerts();
        foreach ( $alerts as $alert ) {
            if ( $alert['id'] === $alert_id ) {
                return $alert;
            }
        }
        return null;
    }
    /**
     * Get unique ID for an alert.
     *
     * @param array $alert Alert data.
     * @return string
     */
    public static function get_alert_uid( array $alert ): string {
        if ( ! empty( $alert['id'] ) ) {
            return (string) $alert['id'];
        }
        $title = $alert['title'] ?? '';
        $date  = $alert['published_at'] ?? current_time( 'mysql' );
        return md5( $title . $date );
    }
    /**
     * Get external link for an alert.
     *
     * @param array $alert Alert data.
     * @return string
     */
    public static function get_alert_link( array $alert ): string {
        return $alert['link'] ?? '';
    }
    /**
     * Get upcoming compliance deadlines (within 90 days).
     *
     * @return array
     */
    public static function get_upcoming_deadlines(): array {
        $alerts    = self::get_alerts();
        $now       = current_time( 'Y-m-d' );
        $ninety_days = gmdate( 'Y-m-d', strtotime( '+90 days' ) );
        $upcoming  = [];
        foreach ( $alerts as $alert ) {
            $deadline = $alert['compliance_deadline'] ?? '';
            if ( ! empty( $deadline ) && $deadline >= $now && $deadline <= $ninety_days ) {
                $upcoming[] = $alert;
            }
        }
        return $upcoming;
    }
    /**
     * Get unread alerts count for current user.
     *
     * @return int
     */
    public static function count_unread(): int {
        if ( ! is_user_logged_in() ) {
            return 0;
        }
        $alerts = self::get_alerts();
        $read   = get_user_meta( get_current_user_id(), 'megagovern_alerts_read', true );
        if ( ! is_array( $read ) ) {
            $read = [];
        }
        $unread = 0;
        foreach ( $alerts as $alert ) {
            if ( ! in_array( $alert['id'], $read, true ) ) {
                $unread++;
            }
        }
        return $unread;
    }
    /**
     * Mark an alert as read.
     *
     * @param string $alert_id Alert ID.
     */
    public static function mark_read( string $alert_id ): void {
        if ( ! is_user_logged_in() ) {
            return;
        }
        $user_id = get_current_user_id();
        $read    = get_user_meta( $user_id, 'megagovern_alerts_read', true );
        if ( ! is_array( $read ) ) {
            $read = [];
        }
        if ( ! in_array( $alert_id, $read, true ) ) {
            $read[] = $alert_id;
            update_user_meta( $user_id, 'megagovern_alerts_read', $read );
        }
    }
    /**
     * Mark all alerts as read.
     */
    public static function mark_all_read(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }
        $alerts  = self::get_alerts();
        $user_id = get_current_user_id();
        $ids     = [];
        foreach ( $alerts as $alert ) {
            $ids[] = $alert['id'];
        }
        update_user_meta( $user_id, 'megagovern_alerts_read', $ids );
    }
    /**
     * Get jurisdiction label.
     *
     * @param string $jurisdiction Jurisdiction code.
     * @return string
     */
    public static function get_jurisdiction_label( string $jurisdiction ): string {
        $labels = [
            'eu'     => __( 'European Union', 'megagovern' ),
            'us'     => __( 'United States', 'megagovern' ),
            'global' => __( 'International', 'megagovern' ),
        ];
        return $labels[ $jurisdiction ] ?? __( 'General', 'megagovern' );
    }
    /**
     * Get regulatory status label.
     *
     * @param string $status enacted|proposed|framework|voluntary
     * @return string
     */
    public static function get_status_label( string $status ): string {
        $labels = [
            'enacted'    => __( 'Enacted', 'megagovern' ),
            'proposed'   => __( 'Proposed', 'megagovern' ),
            'framework'  => __( 'Framework', 'megagovern' ),
            'voluntary'  => __( 'Voluntary', 'megagovern' ),
        ];
        return $labels[ $status ] ?? __( 'Unknown', 'megagovern' );
    }
    /**
     * Get severity label.
     *
     * @param string $severity high|medium|low
     * @return string
     */
    public static function get_severity_label( string $severity ): string {
        $labels = [
            'high'   => __( 'High', 'megagovern' ),
            'medium' => __( 'Medium', 'megagovern' ),
            'low'    => __( 'Low', 'megagovern' ),
        ];
        return $labels[ $severity ] ?? __( 'Unknown', 'megagovern' );
    }
    /**
     * Get severity color.
     *
     * @param string $severity high|medium|low
     * @return string
     */
    public static function get_severity_color( string $severity ): string {
        $colors = [
            'high'   => '#d63638',
            'medium' => '#dba617',
            'low'    => '#00a32a',
        ];
        return $colors[ $severity ] ?? '#646970';
    }
    /**
     * Get affected content types label.
     *
     * @param array $types Content type slugs.
     * @return string
     */
    public static function get_content_types_label( array $types ): string {
        $labels = [
            'text'     => __( 'Text', 'megagovern' ),
            'image'    => __( 'Images', 'megagovern' ),
            'audio'    => __( 'Audio', 'megagovern' ),
            'video'    => __( 'Video', 'megagovern' ),
            'deepfake' => __( 'Deepfakes', 'megagovern' ),
        ];
        $mapped = array_map( function( $type ) use ( $labels ) {
            return $labels[ $type ] ?? $type;
        }, $types );
        return implode( ', ', $mapped );
    }
    // ═══════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════
    /**
     * AJAX: Mark single alert as read.
     */
    public function ajax_mark_read(): void {
        check_ajax_referer( 'megagovern_alert_read_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Not logged in.', 'megagovern' ) ] );
        }
        $alert_id = isset( $_POST['alert_id'] ) ? sanitize_text_field( wp_unslash( $_POST['alert_id'] ) ) : '';
        if ( empty( $alert_id ) ) {
            wp_send_json_error( [ 'message' => __( 'No alert ID provided.', 'megagovern' ) ] );
        }
        self::mark_read( $alert_id );
        wp_send_json_success( [
            'alert_id'     => $alert_id,
            'unread_count' => self::count_unread(),
        ] );
    }
    /**
     * AJAX: Mark all alerts as read.
     */
    public function ajax_mark_all_read(): void {
        check_ajax_referer( 'megagovern_alert_read_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Not logged in.', 'megagovern' ) ] );
        }
        self::mark_all_read();
        wp_send_json_success( [ 'unread_count' => 0 ] );
    }
    /**
     * Clear read status for all users (admin tool).
     * Now requires manage_options capability.
     *
     * @return bool
     */
    public static function clear_all_read_status(): bool {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return (bool) $wpdb->delete(
            $wpdb->usermeta,
            [ 'meta_key' => 'megagovern_alerts_read' ],
            [ '%s' ]
        );
    }
}