<?php
/**
 * PPM_WP Class
 *
 * @package WordPress
 * @subpackage wpassword
 */

if ( ! class_exists( 'PPM_WP' ) ) {

	/**
	 * The core class that loads all the functionality.
	 *
	 * @since 0.1
	 */
	class PPM_WP {

		/**
		 * Password Policy Options.
		 *
		 * @var object instance of PPM_WP_Options
		 */
		public $options;

		/**
		 * Password Policy regex.
		 *
		 * @var object instance of PPM_WP_Regex
		 */
		public $regex;

		/**
		 * Policy Policy Message.
		 *
		 * @var object instance of PPM_WP_Msgs
		 */
		public $msgs;

		/**
		 * Store the single instance.
		 *
		 * @var object instance of PPM_WP
		 * @since 0.1
		 */
		private static $_instance = null; // phpcs:ignore

		/**
		 * Password policy menu icon.
		 *
		 * @var string Icon encode string
		 */
		public $icon = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgoKPHN2ZwogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmc5IgogICB3aWR0aD0iMzAwIgogICBoZWlnaHQ9IjMwMCIKICAgdmlld0JveD0iMCAwIDMwMCAzMDAiCiAgIHNvZGlwb2RpOmRvY25hbWU9IldwYXNzd29yZF9pY29uX3doaXRlXzMwMHgzMDAuc3ZnIgogICBpbmtzY2FwZTp2ZXJzaW9uPSIxLjEuMSAoM2JmNWFlMGQyNSwgMjAyMS0wOS0yMCkiCiAgIHhtbG5zOmlua3NjYXBlPSJodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy9uYW1lc3BhY2VzL2lua3NjYXBlIgogICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiCiAgIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIgogICB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgogIDxkZWZzCiAgICAgaWQ9ImRlZnMxMyIgLz4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgaWQ9Im5hbWVkdmlldzExIgogICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIKICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiIKICAgICBib3JkZXJvcGFjaXR5PSIxLjAiCiAgICAgaW5rc2NhcGU6cGFnZXNoYWRvdz0iMiIKICAgICBpbmtzY2FwZTpwYWdlb3BhY2l0eT0iMC4wIgogICAgIGlua3NjYXBlOnBhZ2VjaGVja2VyYm9hcmQ9IjAiCiAgICAgc2hvd2dyaWQ9ImZhbHNlIgogICAgIGlua3NjYXBlOnpvb209IjIuODQ5NjQwMyIKICAgICBpbmtzY2FwZTpjeD0iMTgxLjQyNjQiCiAgICAgaW5rc2NhcGU6Y3k9IjE3My41MzA2NyIKICAgICBpbmtzY2FwZTp3aW5kb3ctd2lkdGg9IjI0OTciCiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTQxNyIKICAgICBpbmtzY2FwZTp3aW5kb3cteD0iNTUiCiAgICAgaW5rc2NhcGU6d2luZG93LXk9Ii04IgogICAgIGlua3NjYXBlOndpbmRvdy1tYXhpbWl6ZWQ9IjEiCiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0iZzE1IiAvPgogIDxnCiAgICAgaW5rc2NhcGU6Z3JvdXBtb2RlPSJsYXllciIKICAgICBpbmtzY2FwZTpsYWJlbD0iSW1hZ2UiCiAgICAgaWQ9ImcxNSI+CiAgICA8aW1hZ2UKICAgICAgIHdpZHRoPSIzMDAiCiAgICAgICBoZWlnaHQ9IjMwMCIKICAgICAgIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiCiAgICAgICB4bGluazpocmVmPSJkYXRhOmltYWdlL3BuZztiYXNlNjQsaVZCT1J3MEtHZ29BQUFBTlNVaEVVZ0FBQVN3QUFBRXNDQVlBQUFCNWZZNTFBQUFBR1hSRldIUlRiMlowZDJGeVpRQkJaRzlpWlNCSgpiV0ZuWlZKbFlXUjVjY2xsUEFBQUF5WnBWRmgwV0UxTU9tTnZiUzVoWkc5aVpTNTRiWEFBQUFBQUFEdy9lSEJoWTJ0bGRDQmlaV2RwCmJqMGk3N3UvSWlCcFpEMGlWelZOTUUxd1EyVm9hVWg2Y21WVGVrNVVZM3ByWXpsa0lqOCtJRHg0T25odGNHMWxkR0VnZUcxc2JuTTYKZUQwaVlXUnZZbVU2Ym5NNmJXVjBZUzhpSUhnNmVHMXdkR3M5SWtGa2IySmxJRmhOVUNCRGIzSmxJRFV1Tmkxak1UUTFJRGM1TGpFMgpNelE1T1N3Z01qQXhPQzh3T0M4eE15MHhOam8wTURveU1pQWdJQ0FnSUNBZ0lqNGdQSEprWmpwU1JFWWdlRzFzYm5NNmNtUm1QU0pvCmRIUndPaTh2ZDNkM0xuY3pMbTl5Wnk4eE9UazVMekF5THpJeUxYSmtaaTF6ZVc1MFlYZ3Ribk1qSWo0Z1BISmtaanBFWlhOamNtbHcKZEdsdmJpQnlaR1k2WVdKdmRYUTlJaUlnZUcxc2JuTTZlRzF3UFNKb2RIUndPaTh2Ym5NdVlXUnZZbVV1WTI5dEwzaGhjQzh4TGpBdgpJaUI0Yld4dWN6cDRiWEJOVFQwaWFIUjBjRG92TDI1ekxtRmtiMkpsTG1OdmJTOTRZWEF2TVM0d0wyMXRMeUlnZUcxc2JuTTZjM1JTClpXWTlJbWgwZEhBNkx5OXVjeTVoWkc5aVpTNWpiMjB2ZUdGd0x6RXVNQzl6Vkhsd1pTOVNaWE52ZFhKalpWSmxaaU1pSUhodGNEcEQKY21WaGRHOXlWRzl2YkQwaVFXUnZZbVVnVUdodmRHOXphRzl3SUVORElESXdNVGtnS0ZkcGJtUnZkM01wSWlCNGJYQk5UVHBKYm5OMApZVzVqWlVsRVBTSjRiWEF1YVdsa09rWTROakk0TUVZME0wSkRRekV4UlVNNU9VSXhPRGsxUTBaR05URXlRVE01SWlCNGJYQk5UVHBFCmIyTjFiV1Z1ZEVsRVBTSjRiWEF1Wkdsa09rWTROakk0TUVZMU0wSkRRekV4UlVNNU9VSXhPRGsxUTBaR05URXlRVE01SWo0Z1BIaHQKY0UxTk9rUmxjbWwyWldSR2NtOXRJSE4wVW1WbU9tbHVjM1JoYm1ObFNVUTlJbmh0Y0M1cGFXUTZSamcyTWpnd1JqSXpRa05ETVRGRgpRems1UWpFNE9UVkRSa1kxTVRKQk16a2lJSE4wVW1WbU9tUnZZM1Z0Wlc1MFNVUTlJbmh0Y0M1a2FXUTZSamcyTWpnd1JqTXpRa05ECk1URkZRems1UWpFNE9UVkRSa1kxTVRKQk16a2lMejRnUEM5eVpHWTZSR1Z6WTNKcGNIUnBiMjQrSUR3dmNtUm1PbEpFUmo0Z1BDOTQKT25odGNHMWxkR0UrSUR3L2VIQmhZMnRsZENCbGJtUTlJbklpUHo0aHVISllBQUF5L1VsRVFWUjQydXhkQjdnVlJkSXRIaElVRlVVeApvb0tBQ3laTWlMb0tpSWc1ZzdybXJPdGl6bGt4WVZvRHJ1c2FXZGVjMXB3VnhCd3dncm9LaWxsQVFGRUpTdmo3MlBWK25vOTc3L1NFCkRqTlQ1L3ZxUTkrZDZabnA2VDVUWFYyaHlkeTVjMGtnRUFqeWdBV2tDd1F4c0lpU3BaUXN4dEpPeVlwSzJpcFpYRWxySlFzcHFlTi8KcHl1WncvLytxR1N5a2dsS3ZsRHlwWklwTE44cG1TSGRLeERDRWlSQkhSUFI4a3JXVkxLaGtuV1VkRkxTd3NMMWZsYnl2cEtYbGJ5Zwo1Qk1sNDVSTWsxY2hhSWdtc2lRVU1FRjFVTEt1a3UyVjlGV3l0T2Q3bXFua1dTVjNLM2xKeVZnbE1saUZzR1FNbEJRdFdXc2FvR1F2CkpVc0dmcjlZU2w2djVDNGxvNFM4aExBRTVjQjZTZzVWc3ErU1pqbDlCdGpDTGxOeWk1S3Y1SlVLWVFtS2hUWks5bGR5R21uamVKSHcKQ2ovWE1Ibk5RbGlDZkdOMUplY28yYmtFenpwSnlRbEsvazE2WjFJZ2hDWElDYm9yK1NkcEkzclpnQUY5aXBLTFNleGNRbGlDb0xHYQprcHVac01xTzM1UWNxZVJhNlFvaExFRllnSTFxcUpMdHBDdm1BM1lYc1F2NnRIUkYvbEVuWFpCN25FN2FmaU5rVlJud3pIK0t0RkcrCnJYU0hhRmdDUDRBUDFRTktWcEN1aUlYalNidEVDSVN3Qkk3d2R5WEhCSGhmaUFmOGxiVDlhTGFTNXFURHYxcFNXR0ZnN3luWmhzU0gKU3doTFlCWHRTZHRpT25tNE5ram9heVhmODc4Zkt2bU1kQ0F6N0VRSWJ2NkJTV3NPUzFNV0JFSXZ4a3N5eENldXJLU0xrbzZrUGV3UgpSTjNLd3pQOWxjUW9MNFFsc0lMOVNPOEF1c0pFMHZGN0NFaUdEZWcxSmlRYlFFQTFQUEMzVXRKYnlaL0lYYWpRdzZUakp3VkNXSUtNCjhDOGxoMWkrQmdiQ0oweE9JTWEzUEQveitrb09VcklGNmN3Uk5nRU5zWStTMFRMVWhMQUV5UUViRUhhM05ySjREU3pwN2xCeUJlbTgKVkNFQ0d0ZkpTbmJrcGFVdDdLM2tWaGwyUWxpQytFQzZGOFRKMlVyejhvU1NNNVM4bWJOKzJVM0pXVXE2V21yL0lpWkhnUkNXd0JBOQpTT2VBYXBweHU3TjVlWWtKK1ZQTyt3ZzJyeUZLTnJEUTlyMmswKzRJaExBRUVlaW41RWtMN1Y2cDVEZ21yU0toRzJtYjI5b1p0NHNQClJrK1NRR29oTEVGVllMZnF3WXpiUk1iT0EwbW5JUzR5WUp3ZnFtU1pETnNjeFJyY0x6STB3NENFNW9TREhUSW1xdzlJK3pydFZnS3kKSXRaS2wxVnlkb1p0SWozUHU2U0xhd2hFd3hJd05pZnRUcEFWamxWeWVZbjdFeHNWOEsvS0ttc0YvTkdRcXVkSEdhcENXR1VIWWdKSApadFFXU21mOW1mOFY2RXlrNTJYVTFudjhybVpMdHdwaGxSVUlVWGtyb3lVSENqUWM0dmorNjFpYmdXL1VvcVE5MWhkVXNqREdGaTlGClVaTVE0VHF3QTZFR0ladzBmM040ajNCQWZaU3k4Wndmcm1SVEdiWkNXR1VFSmpqc0krMHphR3NQMHM2ZnRyR2NrbFZJTzdMMklyMUQKRjlkUERLbHdVSU1RdTNBamxIeEUybm5WNmpqbkpYZmZETm9TbHdjaHJGSUNOZmY2cEd3RHZsU2JNUEhaQWd6MzJMMDhrTW5LQnJDRQpSUVdjKy9sWmJDMjdrRmJtMkF6YXVVVEppVEtFaGJES0FqZzhEa3paQnVMK2V2QXlLMnNzeFZyYlNaU3RtNEFwQ1YvT1Mxd2I2Vi8rCnB1VHFETnBCbk9PTk1wU0ZzSXFPZzNneXBnRXlKNkI4Zk5ZdkQwYmw4NVZzR1VoZklZNFNsWEJHWnR4dWZ5WDNaTkFPM3NHck1xU0YKc0lvS2hKTzhrYktONTVSc2x2RjliY1phWDlyNFBCaldwNUZPNGdlN0VRcTFJcy9WUWluYmZZZDA4ZGZYTTN4bU9KbytrYktOejBrWAovaERIVWlHc3dnRnhnZGdhWHpWRkd6QlM5OHJ3bnJDRGhqcCtYV0tlTjFYSk9OSkovS0R0dmMrVEY5a2VacEsyUVlHd3NJdUliS013CnpMZFhzaVpySmNpK3NISUNJaHV1WkIvS3ptMmpGN2VaQnJDOTdTdkRXd2lyYUVENmxxTlNuSStzQ2xrNVFpNUJPbzFLbktYZnQ2emQKRGVWLzA4YllOZVByWTdMMzVuc3lCWmF0cDJmVUY5ZzVURnRSWno4bWZvRVFWaUd3TjMrSmsySXNheWZUTXJpWEk1UmNaWGdzL0tVZQpVM0pCeHN1eGFzUnhLcG43T1VHajI0cTF2TFJBWmV6N1Vwei9LMnVONDJTb0MySGxIWXVUanV0THV0dUdYVFA0TzMyVzhqN2czUGs0Cm1hVmpRUWpLWmF6SnVNNVdBQ2Zhd1VvT0pyUDBPaWlXT2lTRDZ4NUd1bHAyVXNDSXY2c01kN3VRNEdmN3VJalN1UWJzbkFGWndhZysKeVlDc3ByRUdCbkk3bC95a1ZnRlpvamdFc3EzK3crQjRhSXQzWm5EZGEvbGRKY1VBdm0rQmFGaTV4ZGFrdzBLUzRtalNlYXpTQUhYNApMakVrMWhBemJhS3E5VzBVYlcvRGhnWTg4TlB1Mk1HVGZaZUU1MzVEZXFkMXFneDlJYXc4NGtYU3djaEpnS1IwQjZTOC9nMmtQZFJyCjRRWFc0cjRQdkM4M1pUSnBVK09ZOGFTektueWRaazdRdk5ROFNZQ2FrY2ZKMEJmQ3lodU9US0VkUVZ2b2x2TDZkMU4wek50K2xML2QKTFd4ZTdGM2o5MStZdFA2WDRocklYcHFtYWhBMlNONlhLWkE5eElabEJ6QVdwM0ZoT0REbDlZZEdrTlU0MHVFM2VkeUtoeC9XSGpWKwpoNk1xWEVEYXA3akcyNlIzTEpOaW9Fd0JJYXc4QWVFa0t5Yzg5eHhLVjhrR08yYTFIQm1SMVFFVmVTYm11SC94RE1nRytrMlYzNUhlClpqaWxTeWx6SVNWUHFvZzBQOTFsR3NpU01BK0FaL2NZMGlYWjR3STJyMDFTWFBzWTBqYVVXbVI0ZG9INmVnSFNZVHVyVmZrZHZtTTkKVXJRUExRM3BiMW9rT05kSGZqTFJzQVN4Y1hSQ3NnTFNHR3UzamlDcmdRVWpLMkFXYVh2UmlDcS9JL1RvcGhUdFkrbWNOR1Bwd1h4dgpBaUdzY0RWVzB0a1lrdUJhU3U1TmpzUjZkOVg0SGY1Qi95aG9uOE5YRERHQnIxVDVmWC9TZ2ROSmNWNktKZnJlTWlWa1NSZ3lEazlJCkROaUdSMmhIVWgraUIwaFgzYWtFNUxTNnVBUjkzNXlKWlkwcXZ5TjF6dHNKMjA1YWZnMjV5cEQwOEh1WkdxSmhoWWpkRXA3Mzl4UmsKZFhBTnNycW9KR1FGL01xYTF2Z3F2OStRb3UySFdPSUNZVmtIeXJRUURTdEVKUFZxaDlFNGFkVmkyTW8rcHNwcFdoNnFRV1JGUnEwcQpSS2VRamxOTUFualJ2NVRndkpjcHVmT3dRRFFzYTlnbTRYbHBzbytlVUlXc3NOMi9WMG5mQXh3K2o2N3lHMXdWVmsvWTdzdTg5RTVDCmRGdkk5QkRDQ2dudyswa1NxVDlheVRVSnI0bnNwVWZWSUxLZlN2dytFR0h3V0pYZjBoU2hTSm9Wb3E5TUVTR3NrQURiVlJJbnhidFQKWExOYVpnRHNGdDR1citUMzBLZ1pGZjZPWGNPa1dWdVJ1SEJZZ3ZPMmw5Y2hoQlVTa2poN3d0UDhzb1RYZzUybVVtRDBkTXFtakZVUgpnS1NIbDFmNUxVM29USkpVTnRncDNGRmVpUkJXQ0ZnczRXREVraVhwem1DMTVTZVdsOS9JSy9sL0lJM3ltQXAvNzU5Q3k3cU95VEF1Ck5wTFhJWVFWQW5haVpLWG1rMjZ6dzBtMGtsRVpCU0RPbE5meEI4eGhncW1FTklVam5rbHdUaDk1SFVKWUlXQ3RCT2VnMHN5TENhL1gKanlySHRzRnVOVTFleDN5NHBJcVdCVnRXMHB4WFNiSmNJT1hOQnZJNmhMQjhvMmVDYzE1TWNiMUtxVlZRTE9JS2VSVlZVYzBkSWVudQpIY0tBa29UcmlEK1dFSlpYZEUrb1lTWE5RWTZrZnB0WCtEdWNSTCtVMTFFVjExRGwvUFJwZk5XU0ZNVHRJcTlDQ01zbmtoaFNSMUx5CllOcHFIdkVQeXF1b0NSVHhlTHpDMzVGNlp2MkViZDZmNEp6ZThpclNZUUhwZ2xUb2tPQ2NOSFgwS25uVFkxZndEZ2ZQQ3JzWmRrU1IKMFhOQjBoNzJ5UDNWbk9hVm8xKzRnaWFEN0t1d3JmM00vOEkzNmxmKzczcjVnYlJMaGswOFg2WC9vTFVteVpJQm42d3ZsS3dZNDV4TwpySlcvSVZOSENNc0hrdVJkZnliaHRWQVplYXNLZjBmSXlLeU10RzJVSTRNRGJGc2xuVWxua09pb1pDVWw3VWp2aGpiTnVBOUJjUERLCi81WUo0RE1tZFNUT1F5RHpGQ2JsMzFKZUI2UitRWVV4ano2OVB1Rjl2eGVUc0lnMU9pRXNJU3puQUlHc0YvTWNhQlpQcGxnT3RxcncKOTFjVHROV0tDYWc5VDZBTlNkdmlsdlZrbG1qTlVzM0dNNFhKNFZVbTZFOUpwK1NaRXVNNlg1R3VFTlM0c2pSc2dxakVNem5CdmFQUQp4TFl4ejFsQnBvNFFsZytzeFV1Z09FQStwdTh5WG42YTVCMWZtaldsalVsbmxkaUFrcVg5OVFXa2FlbEZmM1QyaElhRFRCZXdUU0ZjCkJsa3JvallleGxRZ0xMeERaQVlkbnVDK2NNNHBNYzlwTDFOSENNc0h1aVk0NTRzVTE2dVVtL3hEcWx4T0N0b0tFdGtoUzBCL0t1YnUKRkRTemRWaE80Nzk5VG5vRDRsRW1zd21Oem9ILzI4RVYybG94NFQzQTlvV2lxWXRhTmlNSWhMQlNZK2tFNTR6Sm1MQSthTFRVNkUwNgpZVnl2a3I0VDJOcU9aS2xmc2wzUHkzQm9ZSEQvbUZWaDNLK1M4SHJZTFBpSTR1MDByc0xMOGE5a0NnbGh1Y1JTQ2M1Sldwd1RHa0NsCnlqQ0lSWVRSK0NyU08xQ0NQMklON3B0NjdmWmZySVYxckhCY1VzUmQ0dGZ4dXhMQ1NxaFdDNUtoWFlKemtsWURSbWJSSmhYK3ZqY3YKZjRTc3pFai8vQXBrQmF5YW90M3hDYzZSOXlXRTVSd2RFM3lKeHlXODFoSlYvdDZrQ3BFSjRwUFpjZ25QVFpLNW9ZMTB1UkNXUzJCbgpLVzdDUHZnWnpZeDVEdHdNQmltNVJicmNLdUQ4K2hFdkdWZUxlZTZuQ2E2M2lIUzVFSlpMTEZkRDY2bUdPRDVEY05qRVZqMGNKczhnCnZhMHZzQXVRQ0NvMWoyTHk2bTJSc0ZwTGR3dGh1VVNTQVdlU3JBKzV0ZDZKT1dFRTJhUCtnd0cvTHFTaXJyVTVOU21oaGk0UXduS0cKSlBuYmY2N3gyejZraTIwaW9GYjhkTUlCTmxhUTZRRVJDc2hlMnFJS1ljWDFrbDlNdWxZSUszUU5xMUp3TDVMSXdmSHczd21XbUFKMwp3TWJHdWFRRHQ4OXBwSEVoRHZMSG1PM0pFbDhJeXlsYUpUam4xd2IvM1o4MXFwdElETEI1QTlKUUl4RDd1RWFrSlJxV0VGYWhnQ1VoCnRyUGg3WDZQYUZTNXg2WDgwZG1LdGVRNFdFaTZMNkdxSzZYcUUrRXdKZjlNc0NURVVxS1pkRi9wQVdOK2U2cWNCVlZRQXhLYWt3eEoKY2tJdEtOMG1hTEN5cVJQQ2tpV2hLNGhhS2tpcktJaXlJSVFsRU9RQ0M1SFlzWVN3QklJY2FlaWlwU2RVVFFXQ09JQXZFc0tNSnRJOApwMGxzNjJNWEZMR1M4T2lIYmFZSi85dUN0UW44aTBSM2k3Tmd4eFE1eGVEVzBiUmtmVGlIeEg0bGhDVklEZmdYZmMxRWhQeFJ5S00rCm12OEdVcHJLOG1OR0V3NGJFYTJadENDSUlFQ0NPM2o3SXp3R09jZVdvK0w1cXJXZ2ZLV29Gc0xLT1dZWDVDdGZYNlVHbFl4SGtLN20KTXRuaFBVeG5hWmdFcjNHT2VtUlNRSUs5M3FRenFZTFFrRm0wcFJDV0VKYkFESGtNcllETkJOazJrZlVVZWMrZm9QbHpub2NJUkFpTQpaTG1NLzRZbFpoOGx1NUF1cU5FcFoyTVp0dU4rU202VXFSUVA0amdhSHloaWNGMk83aGZwVW9ZcXVaMTBUcTRpQXFFdS9mbmRySitqCis3NVN5ZEV5cFlTd2JPRm1KZnNGZm85WVlyM0FKSVdpclJOTDlvNFE4b1J5WmdjbzJZeVN4WDI2eEl1a1M2LzlKTk5MQ0NzcllFY0wKTnA3VkFyN0hOMGtYWFBpUHZLNC9ZRmNseC9EU01WVDh4RXZjTitWMUNXR2xCWGFzVUhFNFZDTXZTQW9wVHliTHE2b0o3RWFleGVRVgpLblpYY3BlOHF1b1F4OUhhMklGMEJ0RFF5QXB1Qm52eGZSMGxaR1VFdUdJY1MzcDNiaS91dzlCd3A1SVQ1VldKaHBVRUp5aTVPTEI3CkdxN2tiQ1hQTzc0dWRrWGIwanpIVDJUaVJPSFdwWGk1M0liSkV3NmdjL25mT1RUUG14czdmVDh6c2NLbUJqY0cxT1ZEaWF4NnY2NkoKVkRzcnF3MzBadTIwWjJEdkdiYlNBMlFLQ21HWkFxbGpEZ3ZvZnA1VzhqY2xuMWkrRGtnSEphOVFCM0U5MHRXbTF5U2RDc1YyV2h4cwpGaUJYR0p4VlgrZC9VWVFEdm1JekxGKzdpNUpyS2F5SzJjZ3AzMGVtb2hCV0ZHNVRza2NnOTRMQ3F6dFR1aEwzVVVCOXhXMUo3MVJoCmR5MjBvRnlFK3NDeEZRNmxLQnI3Z2NWcndTa1ZlZlZEMlZ4NWl6OGFzMlJhQ21GVndrTkt0Z3ZnUGxBMVo2Q1NaeTIwM1lHLzNQQmIKd29iQ3NqbDdSeE81Zi83TG11ZEhGaWIwNWtxR2tBNFA4bzMvOFlma2U1bWVRbGdOQWJlRlRUemZBMnc5Y0g3TXVuQnFWOWJVRGlWdApleW9TNEJLQUloNjNzMFl5TThPMjl5UHRKT3c3U3l6c2ZYREwrTHJzazFRSVMrTWxKUnQ1dm9jYm1LeXlBZ3pqMkEzREx1SXlKWG1QCnNIVWgzT1VtSnErc2NMMlNnd0xRTEx1VERxOFN3aW94c09QVzAvTkE3RXZheUp3RjBOYWxKUFVOa1hIaWJDVlhaOVRlNnFRakI1YjIKK0V5SS9WeWI5R1pFS1ZGMlA2d25QWlBWUmFSZEE5S1NGU2JSZWFUdEhFOExXZjBPaE9nTTRTWGlyUm4weVNqV1ZNLzMrRXdZS3dqbAphU3NhVnZtQUhhZXRQVjBiVy9Vb0Q1VjJ4NnNyazk1MkpEQUJkbDNoNlo1Mk13TTdxNCtSM2xYMGdiRksxcVg0QlZ5RnNISUtHTFgzCjluUnRHSWozUzlsR1p5VjM4S0FWeEFjY1YvZktnTGdRRm5WRWpPTlIzZ3ZaV3B0WCtBMDduZkNENjJUWUZ1SU91d3RoRlI4WEtqbloKMDdYM0pMMmJsWWFvWUZEZVdEZ25NMDBGeHZUaEtkcllRc25EWkxhVGlHT2ZxdkU3TmtyR2tYbkthS3dTdGkzVEN5dWJEZXRjVDJUMQpGZy9HcEdTMU1rK0tqNFdzTWdXV2RzTllXK21Sc0EzWVFXRmJNZ21YeWxvNzJJWjBHaUVockFJQ29TMm5lN2p1TmJ4MFMrSkRnNlhECkRhd0psT3BMNmhoNFA4aklBYnZVa2duTy80RjBYT0xKR2N5M0pqR3Z2YStTZndsaEZRdEk1SGExaCtzT1pLSk1BdGhZc01OMW9QQ0oKTTJBakJHNG1weVE4SHhzZy9UemM5eUZLamhQQ0tnWVFpbktmNDJ1aVNNV1dTdjZSNEZ6c1BLRVloQ1RpODRjTFdLdE5rdlR2YVY1cQp1bmJ3dkxRTVdualJDUXZxOVNPa2s3ZTV3bmRNT2s4bU9CZStWSWdkVzA4NHd6dGdOMFRROWJVSnp2MlVkSWFMWnh6Zk01TC9kUmJDCnlpOFFlYitxdyt1TllyTDZOT1o1K0NMRG9INmE4RVJ3UVB6bGhJUWZFUVJSdTdRdkxjUmpYZ2dyaHpoVHlZNE9yemVjZFAyOHVNVUUKamlTZFBxYXpjRU93YU12TDlITVNuSXU4YW9QNHYxM1VJa1FJMFQyRlhUSVYxQStyTDlzU1hPSEJCT1NJNVNwY0ZiWVJQc2dWc0p2WQpMOEdINlN6K01OMVc0eGk0dm55ZWtTS0JWTXVYQ0dHRkQ2VHhIYzB2M3dXUWgvc3ZNYzlCRmsvNC83U1IrWjlMWVBjV09jVmV6cmpkCkxBa0x3S2JCYTdJa0RCdTNPU1NydXhLUTFUNUszaFd5eWpXd3RFTktvaU1DdjgraEZOK3ZTd2pMSVZBNHd0WFdMdXdFdThjOFp6RHAKV0VKQk1ZQll3cENkTnJzVWJid1ZhVW1JUE56dk9TSmhlRVRIdFQyQjRQckxIQzhrNEw2d2VZQkx3bnJzUmJWdFowSllIZ0I3d29ZTwpyb044UkhGU0tiZmtjeVN6UXJIeEllbmNhbWx5cjlzaUxKUlRReXFpS2JJa0RBUG5PU0tyRDJKK1NiRWRQbHJJcWhRQUlTQ3NwMStBCjk0WUVqemNVb1pNWEtNQXpZTWZOaGNNbFV1NGkxc3kwUmg2K2x2RGRXVWJtY21HQW5GVlhrSFlrYmRYb054UVE2VUYrVXlqWEFvcVEKSUZBNjF6YXRJaXdKaDVPYkFwalE0RjZOUVZZb0JycXN6UEZDQWI1WDdVbFhzTFlCVzB2Q2VpQmpDQ0l4cHNtUzBBK09jVVJXZThZZwpxK1dGckFxTFpqbGZsV0JzRHNuekM4Z3pZY0dQNlZRSDE0R0g4dTB4N3VrRklTdEJ3RGhBeWFaQ1dPNEJXOEtTbHE5eEw4MkxBNHRDCkMxNmVkcEE1SVFnYzUrZjF4dk5LV0wzSmZoRUp4SDN0RStONCtHYXRJWE5Ca0FQQUhwdkxoSDk1SmF4ekhGd0RoRGpkOEZqVXZlc2oKODBDUUl4eEw4KzkwQ21GWkFNcTUyeTUrZWpTWkc5bFIyR0pQR2YrbHdCd1dXNWhydWYyR1dJNTBTdWRjSVk5dURYRGU3R3F4ZmRpdApCaGdlaTVBSFNXVmNIc0FIYngzU1h1MVoyMC9oSVErZndyY2RLaEpJNVkxNHd6RkNXSFlBQjlIekxMWVA1MURFSkk0M09MWWJENjRtCk1vOUxoWm1zQldYOTN1Y3lVYlZ3L0R5MzUybUZrQ2ZDUWt6ZUoyUTNkUXpzVnJjYUhJZENsKytRenU0b0VPUWRxQ0Q5Wmg1dU5FODIKckRNc2s5VXRobVFGM0Noa0pTZ1FUc25MamVaRncycko2K3psTGJXUFVBdll4U1lZSEhzNEpTdmZKUkNFakZ4a0o4MkxobldHUmJJQwpUak1rSzVEYWxUSzJCUVhFQ2FKaFpRUEVic0YyMWQ1Uys0OHIyZHJ3V0JTMjZDdGpXMUJRckUzYU5pc2FWZ3FjYUpHc0FOTmR4OU9FCnJBUUZ4L0dpWWFVSEdMK2JwYmFSay9zb2crTzZoZjdsRVFneUFISjZvYWp2VjZKaEpjTytGc2xxU2d6dDZrSVp5NElTb0RrRkhtTVkKT21IdGJySHR5MG1udEkzQ0lhUXpqUW9FWmNEMnNpUk1CcVNiZmRWUzI1OHBXWldpMHgwdlRqb251K1MzRXBRSkF5bFExNTJRTmF6RApMYlo5SlpubFpqOUx5RXBRUXV3cUdsWThMS0xrVTdLVG9BKzFDMDNzWW5Da2UwWEdycUNrNksza2VkR3d6SEFJMmNzbWFsbzE1R2daCnM0SVNZei9Sc015QlNycWJXV2dYV2h1cWhzeU9PRzQ3SlEvSm1CV1VHRjhxV1psMGFUUFJzR3FnbXlXeUFzNHpJQ3ZnR0JtdmdwSmoKQlNVSHlwSXdHZ2RZYWhlNXJ1NDNPQTYrWDV2S2VCVUlqRVBXU2sxWW0xaHE5MUlsUHhvY2Q1Q01VNEhnZC9TandIYkpReU1zRkVWZAoyMUxiSmpZcGFGY2J5emdWQ0g1SFM1NFRRbGhWWU11ei9UYlN1ZUJOQ0VzZ0VNeER6NUJ1SmpUQ1d0ZFN1M2NhSExNVGllMUtJR2lNCjNrcVdFc0thSDkxWnNzWTQwam12b2pCQXhxWkFNQjhXSkxzeHZia2xyTjBzdGZ0UGluWmxXRi9KWDJSc0NnUVYwVXNJYTM2c2FhbmQKNFFiSDdDSmpVcEJ6VEZQeUJ2OXJZL1VUQkZlRVFsaFlJOXR3WjBCUy9kY2pqa0VPSUtuY0xNZ3JVSUQxTEo1RFdDbThiT0VhY0NJTgp3aWNyRk1MYWtmUVdhdGE0dytBWVZHOWVYc2E5SUVmNFZza1Z2Q29CVVExUzhndi9kcnVsYXdhUkUyNkJRRjdBUnBiYWZjbmdtQzFrCi9BdHlnaDlJdXhtOFgrT1lGeXhkdTBzSUhSQks4RFBXM3V0bDNPYWJGTDNyaUVEby93VStTSkczQzBIYjQ1UzhTN3FDMEhmODl6cVoKdzRtQVRSaVVoRytycEFQcCtOV08vTit0QTcvM3pxUnJkTllDbktTM3kvaTZNMGtYZy9tdTdCcFdGd3RrQmR4cmNFeW82V0NuS3htaAo1Q1llZkROSTRBcnd4VHRZeVpha004NkdCdXltbng5eHpIMFdDS3NGOThsUW53OGZ3aGU2dDZWMjN6UTRKclF3SEJBVHF2Z3N4SVBqCmJpRXI1eGltWkE4bGJaVHNvT1Ryd083UFpNeU9zblR0RFh3L2ZBaUVaVU83K3NHQXNEcnlnQXpGTm5Fb2FTZTlxNFF6Z2dHMDIzYWsKRGM2aG1BNjJwR2g3MGtnbGIxbTRkZ2NoTER1ZGdJSDJvOEdMRHdGRGVPbHhuZkJEc0hpQ1NXSmdJUGZUMTNBT1pBM1krbHI2ZkhEZgpoTldLN05RZEhHYlkrVDRCSXlaMlI0OFVQc2dOVUVrR2JnUmpQZCtIaVpPMURRMXJhZEsrWHFVbExBUTdMMkdoM2FnQjFZejhPc0s5CnhpOWZpbHprRDZobDJZbDBCaENmeThLb0RUTVVXN0hoQXZEbk1oT1dEZS8yU1FaZmx6N2t6MWtVT3pnYmtGa3lRVUc0Z01QeHVaNnUKRGMvenFQaSt6NVc4YU9IYVh2MnhmQk5XWnd0dHduSHVsNGhqMXZiMHZBOHE2Uzl6dlRBNGszU2RBQjlZeCtDWTV5eGN0MTJaQ1dzNQpDMjJhMUZKYjNjT3pEaWNkZ2lRb0ZzNGduUkhFTmJvYUhQT0JoZXQyOXNrYnZnbXJvNFUybyt4WFRjbDl1Z3pZRXphVHVWMVlvRXI1Cm80NnYyZHZnbUhHV2xxT3JscEd3NE02d3NvVjJ2NGo0dlljSHRYWWZKWE5rWGhjYVNGSDBpZVA1RTdVc3hJZHlnb1Zycis2cmszMFMKbG8ySC9rYko2SWhqMW5UOG5LZ2cvYTdNNThJRGJpcEhPTDVtRkdFaFNtS2toZXV1SllTVkRkNm02RXExS3poOFJ0alRycFM1WEJvOApTZHBYeXhWTXh2SWJGcTY3ZEJrSnk4YXl6RVNUNmV6d0dTK1JPVnc2WEV4MnNuNVdRbnVEWThaWXVPNFN2anJYSjJFdGFhRk5FeHVDCkt3LzNlOGk5SVZiZ0g3Q2hYdXJvV2laTHN3azVtYnRHOEpsZXhrYnFqa2tSdjY5RU9nZVdDOXpoNkRxRFNVZndqMi93dCtiOGJoSDcKNWlxTVpITWx4NUhPaHRuUXd4b091aWl6ZHJPaiswQ3NHK0l5VWJGNGFvTy9MMG82bDlPaERqU2dvVXBPZDZBUXdJbHpHYXFkbzhvRwpZYlhqWjNPK2tlU1RzQmF6ME9iRWlOLy81T2paa0VmK3Y0NnVoWExpMVJ4aDJ6b2tMQmlBcTJWdkhlK1FzSkNhWnhmK3R6RitJbTBZCnQwMVlueW01a1hSZUxadG96cVQxWGNTOVRHWEN6Z3I0Q0MzTkg2ZFNMQW1SREd4RkMrMUdmVTFjRWRZRER2dnl0eHEvelhKNEg3WHkKZHYzcThENXdyWmxWZnB2alVDdDR5dEYxMmtmOGp0UkZXZWYwcXVQVmluUDRJcXpGTGF5RDhWTEdSUnpUMWRIekRTTkIyWUV3ckc4YwpYTWVrS3JPTlhGNWVkZ3A5RVJic0MwMHpibk84d2RmVFJiNXUrTDI4bW1GN3NNbWdqRk5XaVE2YktEbGV5WVlKemtVRjRLTXpmRGFrCit6MDV3WG1Ja0xncVF5MGRJV0xYVTdZYk10QjhYV3k2dERhY0cxbGpPUi9FNFl1d2xySFE1amlEWXhaejhHeFpWeTFCYnU2elNmdlQKREU3WjFrYmNUM0MzaUJ1MHV6RHBaSU9Yazg2R2tXYnpBaHIyL2FTTjhSY3FXU1BtK1NCYzJLSSt6NEJBajJYdC9DREt2dnIzaHc3RwpXeHVEWTJ5NE5uakpkdUtMc0d4b09qOFlITlBLd2JOOW1YRjdEVk9ZbkVUYVB0T3d3RUF0clhKT2crZUdVK05MRGJRU3BOaUpVK0xzCnFBYkwrTFY1bWRIUWtEN1g0RDZJTmFySlNuWnE4TGM0Ukl4Y1ZJYzErSDhRS0d5WEd6UzQxdHdhOTFGdjE0T0dpU0trbHpYcTMzVXkKZkhjZk94aHZiUTJPK2NuQ2RWdjVJQTVmaE5YV1Fwc201WWNXeVJsaGdTUWFieFJnWndqcGI0ZVQ5blQrT1dJNWVTZ2YwNi9DNzVjWgozZ2RpUGdkVitQdCtwQ3Y4YkVxMURmelQrVGsrWjQycU1iYU9RWjVEcW93bkpFTzhuUW10V25xaFgxZ3p4SEdva0Z6SkFmTFVETitmCnJTUjZEV0hpN2Y1elVRakxsMXVEamFXWnlUcDlJUWZQOWxGRzdheEl0VDNsa1hGaWJJMEpnZHA3ejFMdEhOeXI4ZklxeXRIeHdob2YKdDVaOG5kazF6ditya3I5UmJidmxFSU5sSmtpdFZpNStMT2xnRjJ0UzVmZGxlV2xkYTl6dndocnN3eGw5dkZCVHNxUEY4V2F5V3JIaAp4dEdpVEJxV0RVMW5wa0VIMjdaaFFjdVlsRkZibU1ETklvNXB4aHBYSlRRbHM0SUJJTVZhNlVMMlZMSnJSQnROSWtpZ0dVVnZzblNPCklFN1kwUDVqT0thYjFPZ1RrNC8wVU1vdS9PUjd5Mk51Q1lNUHNRMjNrbElSbG8ySGpTS3NWanpvYlFKMnRLeFNIeS9zNkYzQXJyTmsKeExMU0ZXcE52QVVkVFA1NlRNNVFHNTlzK1Y0WE5oZ3JObXBiTnZWQkhMNEl5OGI2TjhwSnNwbUJ4cElXVFdwODNlTUNDZis2azUzUQppbm9NNW9FM29zWXhOL0l6M1dueFBsQkRFanZIaDljNFppSnJnanVUUFlkWTJOcTJabTN2UzhvSFdocVFxNDMrS3BXR1pjTVFPY2ZnCnhkcnU1Q3dKcTM0aXcwSHZpSXkva3JEUHdGaDdTb3h6WUI5YWk4d3FhcHZpS3lYYk1qR2IrZ3I5bDdXdEN6TWVqMmZ4eEgvY3dwancKUFkvbjVHUU9CMHRZTm14WVVldjBwcFJmWE0xcS82bVV6Z253VVNhZDdaa3M0dUpkSnBmZWxNNDVGdHY5ZXpOcEpuR3VuTVY5Z2ZpNAp2NmZRSUxCcmVBSDM3YUFjajQrV0tlZEdidUNMc0d3c3plWlNzVEdidFlvMEdTUUhVemJaVDVHWThKWVU1OE1uN05ZTTdnUCtSWmRUCmNqK2pxZnd4bUpienNkR3FMSFBERjJIWnFNa1hSWUt6Y3Y2dWp1RmxZWm9Dc1BEQ1I5bjFEaW5hNkVuYWRlT2FGRzFnaVF2YjNHNHAKMnNCUytXN1N0cWFrcVlyZzV2QU5rMmViSEkrTktNSnRMb1NWRGpiSUkycTdlZ2JaMlMyeGpYNDh1YkgweWNJR0IxOG0rQWFadUUwMApudHhQczNhVlJkWUxPSHZDa0QrSzR1ZlpoL2MvSElVSFpOVEhjTjJBTzhvWk9aM0gwejJZUTVyNGVOQTZLZzZpbnVVM3FwMktKYXRsCjIreU0ybHFaeWVGSnNoTVpNSkFIK2dFR1grZExXQlBwYStFK1Z1TmxLcnpQbzN5ZkJ2QXk3blJMNzI4UUU5ZU9HWThKMjVqallaN1AKRG5HUzI0S04yS2FXQnRlY2F2bTVGcVRzL0pZZTR1V1hUZURMQzdlRlBqV09nYXZCOFE3R0JIWWhyNnJ4TzVaOU41RDk4S28ydk5UTQpLdCtUN2VnS0xBZC9Uamsza3VBM0g4VGhpN0JzR0RsTmJGZy9XSDZ1aFRPMGhiaXF2b0xNQzdWS210OXE2UU5UQ1ZmVStHMEt1Y3RhCmVoSHB1TWNzc0pUbGU1MXVzQ1MwRVlMblplZlJGMkhaMEhSTWlHS21nMmZMNnN1TTh1ZXZSQnh6bDVMWGFxanM4Rm1LeWpiNTE0amYKNFYxK1pNUXhyL0dTcmhyZ0F2R0VBVUZIbGFRNktlSjVaakxwVmN1U0FPZFQyTTErcWRFRzNFWXV5K2dkd3UzQ2R0Nm9LUkhQVTYvNQpaNDJaUG9qREYySFpDTEV3S1J2Mm80Tm55ekxROWRRYUV3LzJKQ1RVbTFCanVUZUkrK1crR3RyVDZ3YjNNWlNxdTFPY1R6cTF5LzAxCnprZVE5bFpLOXFYS0d4L1E0TTR5bkNUVm5GMlI1Uld4b3NmVTBDaXduRVFWYnRqS1hxeHl6QWtaYXVLcmtKMWlLNDBKS3dvMmx0RmUKWEVGOEVaYU4rS3JGQStua0xCT2JEU2R0dDJtSTAzaVo4YXpCMTdPK1QvcVRMbHpiY0ptRHIvS3hNZTdscUFvRWdVd0JweHRNaXZyZAp6VnY0ZnE5cjlQdUpaQjQwamdEb054dE4yRTFJMitGbThKaHVWbU5wdEJRVEg4N3AxMmhNdkVSbUFkYW1jRkZTenNRQldEU3NsTEJoClN6TEpDelRad2JObFhkRzZ2bkkwbGxUWUxid2dZVHVqU1Jjc09KRC8vMnFLcmpKRWpTYnpJNlJ0Z2IyWUlKSXU3WkdqQzY0UlkzakMKWFo5UTh6eWRUUUV2SnJ3UHVHbTBZaTBSdUREamQ5ZlJ3WGd6aVh5d1VmQmxvZy9pOEpVUHkwWmdxWW10d0FWaDlXWE5aa3BHN1kzbQo5a0FPV2NTRTNVUzZabUtTdHVCV01EZWpyK3ZIVE83UXZ1SnVrVU83YTVuaFZ4N0VkNUVGcldIOVFGWXJLMWk0N2hkbDA3Q3lYcDYxCk0zZ3hveDA4R3d5dDIyWFkzbHp1cnl3RFdLY25uSnd6TXA3VU14TnFhYk1za0F2c2FGbnVmQ0ZQL1dZT3hwdUpYYmE5SjZJc0RHRk4KcHV5MmpScytTMVRwb1RHT25tOFRoMzFaeStPNGFTQmphUUhIL1ZGWDR4NWRqZmx0SEYwbmFtblcxTktTOExNeUVSWXd5VUtiVVQ0dgppSUZ6NGZDMnY2V3ZXaVhNYnZCdlkwOTdsOTdJOVJyUEw2eXQxQXVST3o4dVlrMTBhb1ByTnJ5UHFRNzdwTCtqNjN3YThYc255dDYxCkFvNnFFM3lRaHM5UzlUWmNHNkpDTzFCYUc3YVQxU3cvRzc1cWUxSDhVbHBKZ0RpNFZ2VEgrTXg2TFdPOHcvY0o3L0JIS2l4ZDY4aXQKa3lHVzBPdnpkZWMyNnBPNVpLY2dRMlBBM1dSZEI5ZEJ2MFlWU2JWUjhIUXMyWGZDRG82d2JQaEVtV2cxN3pnZ0xBQ3hlbGM3ZUxIZgprN3ZVd2JYd0c3bnhjelBWQUh6aUFFZlhnVTAycWxwVVcwdGp6Z3Q4TGdsdFBQVGFHYWpRV1FGZnR1TklVRFlnTWVIbWpxNzFqc0V4Ck5vb1dUL0xWdVQ0Snk4YU9uUWxoZmUzd0diRmQzazNtY0drQUZ3MlhLV3EreUdoT3hNVkVYeDNzazdCR1dWb1NSaTMzUmpwK3ppdGsKSHBjR3lIN2FPVERDNm03aHVoLzc2bUNmaElXSHRoRUVIZVdMaGJDT2NRNmZzN2VTYzJRdUZ4NEhVWFFnZWRhSUNoYkhCM3hOQzljZAo3YXVUZlJMV2o1WWUzQ1Q5N3dqSHozb211VFBFQ3R5akI4MGZIMmtiSHloNVArSVlHNkZCY0RwK3gxZEgrODQ0K3EwbGpjYkhjalFLClNKUzNqY3p0d2dHWllSSG42VHBsOERDRFkxYXljRjBvR2FVMHVnTTJET0FJekkzS2ZmNnVwK2VGbjFKUG1lT0ZBWGFDRVJTK21JZHIKbTBSdGJHVGh1bC81N0hEZmhQV1dwVUcwbnNIWDZSdFB6NHc4N1FOa3J1Y2VzQTE5U0hiY0Jrd1FaYitDeG1jakIvL1hQanZkTjJHOQpiS25kcUxVN25Cd2Y5ZmpjOEFvZkxITSt0OWlidGZURlBWMy9QZGJzYW1FRFMwdkNOM3gydkcvQ3drNmhEWHVTU1pUOCs1NmYvU1IrCithMWsvdWNLeU5KNmkrZDdlTWJnbUpVdFhmdEZudzhlUXBtdlR5eTB1UzFGcDRWOUtvQm54OUlWZ2JrbkN3OEVqMTFKUjJmc0djQzkKdkdkd1RHOEwxOFh1NE5peUU5YUhGdHBzd3lweExTQm85TEVBbmgrMkJtUzZSUHpiSWVRM3ZsTXdQN1lrblVvRkJUK1dDT0IrNEE3MApZTVF4cU42MGs0VnJmK1Q3NFVNZ3JLY3R0ZHZENEpqbkE1b1lXQnIraTNTYUZsUjVYbFhJeXh0ZyswRXRSbVNOZlp6Y3BRb3lBU29oClJRWFVyMitKWEgyYlVhakozTGx6ZmQ4REp1VTR5clo0QXdCL2thajg2cXVTUjY5ZHc2OHBxdEZnZytCakhxajFaWjNta2lBdFVPUVUKTGdtdG1hUTI1NlZmdTREdkdWcDRWQTc4a3luNy9QVDFTc0RyUGg4K0JNSUM0SjlrdzZseVk0cmVUWG1jMWY2OEFFdEgyRkttQmFJaAo1eEZJNG9lYzhFdVNUbW5kSkNmM0RYc25Jam1pSERmaExwUjEwRE5NS0YxQzBHNUN3THVXQ092UFZRaXJPZitHK0s5TmN6YlpGbVlSCmxBOTNHSkJWRDdLVG9lR2RFRG9nbEMvMEk1YmEzYjhST2VORkRtRXRCZVhaOTZCb3IzaUJJQlE4YTNETWhwYXUvVVlJSFJES2toQ0EKTFdsVkMrM3V4Um9WNnZzdEltTmVrRk9ZMkdTQnQ1V3NaZUg2U0pzenhuY25oTFFMOVpZbHdycFZ4cnFnQUxqTDRKaWVsc2pxK1JESQpLcVFsb2FtNkt4QUlZVlZISDB2WGZqZVVUZ2lKc082aE1Jb3BDQVNoQWFtSm9ySjh3a1hqTUV2WHYwOElhMzdBdCtnRkdac0NRY1dQCmVSUzJJRHNsdlJBR05DS1VqZ2pOaitjNUdac0N3UitBSGZRbkRZN2IzOUwxWHcycE0wSWpyUCtRcHdLTkFrR2d1TTNnR0lUaWJPZFIKdXlzdFlTRVVSWXp2QW9FR0VrM2VhWEJjZjB2WGg0dkVNeUYxU0lpaEhRL0pPQlVJZnNjTkJzZDBVbktDcGVzL0gxcUhoRWhZU0k3MgptWXhWUWNtQmxjYnRCc2Zack1aMHN4Q1dHWjZXOFNvb09ZWVlISU1VTXJaY0daRGc4cjNRT2lWVXdycEp4cXVneEVETy93Y05qaHRJCjl2TEtQeFppeDRRVVM5Z1lNUFp0Sm1OWFVFSWdXKzVyRWNjZ2h4ZFN2dGp3dlVKbEhPU0UvMVUwTEhNOEtPTldVRUpjWmtCV3dMR1cKeUFwNEpFU3lDbDNEUWxJMUpMenZJR05ZVUJKQXMrbEtPbEZmTFN6RDJ0V2lsdTZqR3dWb3Z3cGR3d0tUaW91RG9FdzR3NENzZ0pNcwprdFhEb1pKVjZCb1dnRHpiU0dzaHhSZ0VSUWNDakUwY1FKRSs1bTJMOTRHcTVQZUcya21oNXdUL25NdzhmUVdDUEdNR2EwMG1PTW5pCmZid1NNbG5sZ2JDQUsyUThDd29PR05CTkNwUkMrOW5kNG4wRW4rd3k5Q1ZoUFI1UXNvT01hMEVCZ2VEaVhRMlBSU0s5TlMzZHh5Z2wKYTRUZVdYa3BFM1daakd0QkFURkJ5ZDhNanozUElsa0JOK2Vody9LaVlZbVdKU2dpTUo1TmRzTFhJN3RWYXo1UXNsb2VPaXhQaFRoRgp5eElVQ2VlUXVkdk9SWmJ2NWNhOGRGcWVOQ3dBTVZZRFpLd0xjZzc0T20xdmVPeHB2QnkwQlJSSVhUc3ZIWmMzd3VwRzdpdlF3aWlLCnZFQnRNMjUzRHVtQ3JvaTI3eXh6dURUNGxNZnh6d2JIZGxmeXV1WDdPVVRKOVVKWWR0WFhBeHhlYjJjbC83WFkvbjE4RFVIeGdZOFUKN0ZHbWpwOXY4UEcyZ0tJdlBmUFVnWFU1Zk9tbmtFNmw3QXBOTGJmZlV1WnhhYkJ6RExJYVlwbXNnQXZ5MW9GNUpDeHNCVjhwWTErUQpNOEI5d1RRRHlaNmtjMTNaQkFxelBpR0U1UVpuS1hsZjVvQWdKemhieVRXR3h5SmJnKzBFbGtnZGMzSWVPN0l1eDRQZ1BKa0hnaHpnCkt0SXVES1pBV2EvbWx1L3BVaVhqaExEY0FpNE85OHA4RUFRTVZMMDVLc2J4S0RwaDI4VUFLNVBUOHRxaGRUa2ZFTWVRTG5HZlo4eVMKZVYxSUlKRDQ0QmpIbjZ2a0x3N3U2N1E4ZDJyZTgweDl4Uzk2Y0tEM2gzeGViVWhua214V2dhaXdnYkN3ek8zQ0FYRjVjVnh2RGxSeQp1b1A3K2pkcHA5WGNJbzkrV0pYd25KSk5MYldkSnFIWlZqU3Yrc2pzR2xwdUU1bmpoY0hWU282SWNYeGZjbFBXRGgvM1B5bVpsdWZPCnJTdklJTUVXOEp3QTcrdHgvbm9DVGF1SWtGVnhjRjVNc2xxVHg0Z0xuSlIzc2lvU1lYMFE4Tm9jVzlTOWFtaFlnbUpnWDlJNTJlT1kKQzFEZDJZVlpCdFhVYnk5Q0o5Y1ZhTUFNZHZpMWlvc1JTdG9yK1ZMbWRTSFJoMG5CRkV1UkRvdFowc0c5SVhieG9LSjBkRjNCQmc0TQpuVk1DdlRmWUVEb3BlVkhtZDJHQXpSU1VvUnNXNDV6V1RGWXJPTHBIQk5mL0pvUVZKcjVUY25UQTl3Y1A0MDBvUi9tSEJGWHhQSlBPCnVCam5MS1RrVlNXck9McEg1SkI3dWtpZFhsZkFnUVRWL0ZxSDEydEIyblVoRHFDaUh5eHpQcmM0WDBsdjByVXpUWUgwUkFoODd1TG8KSG9jck9iNW9IVjlYMEFIMVZ5VXZPYm9XQmkyQ1dyZUtlUjY4b0R2elVsR1FEMHdublk0bHJzL1VpcVFMU0xqU3JDWlNRUk5kMWhWNApjS0VTeVErT2xua29IZjRZeGM4dE5JYVhGVGNMRndTUHA1UXNUdHIrRkFlb1JETmF5YklPNzNVZkpkOExZZVVMM3lqWjI5RzE2dk56CndhNnhaNEx6c1Ztd0RaT2ZJRHpzcjJRTEpUTmpucmM1NmJMdkxxTVpUcVVjcG8wUnd0SjRoSFRDUDVkQURObVpDYzZEaHJZSW1lZE0KRXRqSENOYXFoaVk0OTJEV3lsd0M5dHNMaS94QzZrb3c2T0NmZFpQamF5S2R5QjBKbDVjN0t0bFN5VlRoQzIrWXdTYUZYZ25OQ3Bjbwp1Yzd4UGI5TTJubTEwS2dyeVFCRWVNeHpqcSs1T3k4SDJpUTQ5MG5TL2pwUzJzdzkvcWxrUWRMRlI1TGdVWEsvTy9jTm14UUtqN29TCkRjU3RTWHY5dWdRTXJqQiticHp3ZkF4OGVFVy9LanhpSGRqRmE2Zms4SVRuSTlUbWN4NW5MakdMTmZJZnl2Q1N5a1JZTUpnaU1uNTgKelBObXB6d0d3YzB2OERJaENiQkZ2U0hwOEk5UndpdVo0ME1sL1pTc1JkcHpQUWxnbEI5SDJuM0JOVUJXcFVrWFhwVDBNbkd3T3VueQpTYWJWYWdZcCtROS9RU3VSSUw1d3lETms0bVB6RVpQbTF5bnVmM05ldG5RVXJrbUZzYVQ5OWRKNmdxTUUzSTZlbnNGMkNUb2hyRUFBClkrcHdqOWZIRjNsb3lqWTI0VFpXRnU2SmhTOUlHNmZUdnYrMXVJM1ducDREWkh0dDJWNWVYVWtITGZ5bHR2TjRmVGlLd2xjbVRUWHAKRjFqTHduTHhJWW9YSmxJMm9HOWdEUDh6YThwcHllcnZwTU5zZkpIVmlXVWtxekpyV1BYWVFja0RudThCQ2QrdXpxQWQ3R3doWkFSeAppa3NKUi8wTzJQL2cwb0kwMmxuay90K0l4MHRiajg4RXY4TEJaWDJoWlNjc1lDY2w5M3UraC8rUnRvTjhsRkY3bUZpb093ZERmYXVTCnZjOWZXUHVFQStXSWpOckV4d0JPbWYwOVB4dVNWRjVRNXNrcWhLVUJzZ2pCZUFualBmSVh6Y2hZaTBTYkd5aFpyS0R2NzJjbEkwa1gKSzcwNzQ3WlJwdXVLQUo3eFZDcTRGN3NRVmp4Z2V6aVVqS1Vua0M1Mm1UVjZrM2FpcmJmbDVObUcrUzNwM1Y0cytXeUVNMkUzRnJ2RApTd2Z3ck1jRVFwcENXSUVCMlJhZUQrUmVwckptZEllbDlqRVJrWUprVzlJNW1sWUluTURnUDRmc0ZrOXpuM3hzNlRvb1pEcVVkSUdJCkVJQ1l4QnRrYWdwaFZVTTMwbkZaQ3dWeVB6QWN3L3ZhZHBWckdKSTNJKzFFdVRvVEdsS2lOUFAwek44eEtRMWprdnJZOGpYaHBvRDQKdis0QmpjVUJKTlhOaGJBTXNBSXZONVlPNko2bThITE9wYTF0Q1Nad2FCdXJrVTQ0Q0ZjSzdFSTJUOWsyeXJJaG5HUWNhMDZvZklUWQp5M2VVZk9iNEEzVkxRQnBWUGVCZy9LeE1SU0VzVXl5cTVKbkF2cmdBcWtVaktCcmU3ai81R0RPa1U2NHN5bG9vQkZFREMvQi9ZMWR5CmRvTmo1L0I5SWhNRklnT1F0Zk1YbHNua0p3Y1l0TWJkU05mcVd6Mnc5enVKZEZIZzkyVUtDbUVsQWZKYjdSbm92YUhXSExhNVI4dHIKTXRhY1VYUVhRZVVoMnV2Z2pOckwwNGNvRjZpVExvakVYcVFyK29hSVBVZ0hSS1BlNFg3eXFxb0NxVmVRalFGaE9TY0dPdTZ4MUY5SAp5RW8wckt5QUpjU2RnZDhqL0xlUTl3dTdTckIvbERVSklKYWxjTjFBNnVtdGVQa2FNczRJK0tNb2hKVmp0Q2NkaDdaU1R1NFg1SFU5CjZRcENSYTg2alIxTnhGWHV6eHBWa3h6Y016SjlZRmQybUV3dElTeWJnTzNvTHptNzUzR2tIU0VSZEkxUW9FazVmd2ZJNUlwSzJuRHcKUkxHUlArWHMvdC9pZTU4czAwa0l5d1h3SmI4cHgvY1BtODdEL0hWSHBrelVSNXdaNkwyaVdPM3lyTm5DS0kxTUcrdmt1Tzh2SnIxRApLUkRDY29vT3BDdWpkQ3JBczhEWSs1cVNWMGo3b0dFSkNkOHZ1RkZNZDNRUGNJdUFqeGRpSHJHanR6N3BHRWo4dTJnQitoZzJ4cTNJCmJ5NDJJU3pCNzEvTUV3cjZiQ0N5c1N5ZnNpYjJKUzhwcDdOV1ZwOTVGZjVYOEt0cWFEK0N6MU5UL2hmT3BpMVpsbVJTV29HSkg2UVAKcDlTaVpwZUFOcnN6OTVOQUNNczc0R0NLSkhGdFM5d0hHRXh6R3YydHFReU4zeXNvM1NYZGtCN2loNVVkM3VEbHpMVWw3b01tVEZBTgpwY3dZenRxa2tKVVFWckJBcnUwZXBPUGpCT1VFbHRIdzI5dVV3dDNJRU1JUy9EOWVKeDBvZkpSMFJlbUFkTmZZSUxoYnVzS0NDaTgyCkxPdUFvZmsrMG5tbkJNVUZza3dndStzWDBoV2lZZVVaMkRXRDM5QzZySGtKaW9VditmMnVMV1FsR2xZUmdjSVFLUE8xb25SRnJvRTgKOGtjcnVWRzZRalNzSWdQeGZmRFlobEYyZ25SSDdnRGZNMVFrV2tUSVNqU3NNZ0l4aVpkVFdObE5CZk5qbXBMenFlUmx0b1N3QlBXQQpGelFxNVhTUXJnZ0tTT004aUQ4cUFpRXNRU05zek1UVlE3ckNLeENLaER4VmQwaFhDR0VKb3JFYzZjS1pxSnl5b0hTSEV5QVdFalpHCjJLamVrdTRRd2hJa0F6Sm5JaVBsc3RJVlZvQXNDaGZ4QjBJODA0V3dCQmtCSmFsUVdITlhLbmVRZFJhQVc4SmpwR00vUjlDOFNqOEMKSVN5QkJTQlBGRXFZby9qcEV0SWRSa0JwTVJUSnZVYkpBOUlkUWxnQ1A0Q0JIdVdyK3BQT0RpRDRJNTVVTW9SMCtoK0JFSllnRUNDVgpjRmNsVzVNT0YrbENPbnRuMlpaNnFCeU44dllvbllWVTBGSTZTd2hMa0JNZ3ZmQXVTbnFTOXZFcW1vTXFpamdnaGc5eG12ZVMzdVVUCmU1UVFscUFnUUJyaXZxUmpHbGRSc2d5VFdCN0tZaUV0ODNlc1FZMWdMZW9kZWFWQ1dJSnlBWG5WVVlVR0dTV3dFN2s4THlVWEoxMU8KeXlXbU1qSDlxR1M4a3RGS1JpcDVVOGtucE5Nd0M0U3dCSUw1Z01JUjhQMUNzRGF5UzdScm9KRzFaVkpERURDcTNTekkwamluT3dMcwprV0lITzNUVG1aRHFTV2s4YTB6MWhTMVFidXhyY2xlbFJ5Q0VKUkFJQkhid2Z3SU1BUEpBUFFWM1l6MlBBQUFBQUVsRlRrU3VRbUNDCgoiCiAgICAgICBpZD0iaW1hZ2UxNyIgLz4KICA8L2c+Cjwvc3ZnPgo=';

		/**
		 * Holds instances of the cron classes in this plugin.
		 *
		 * @var array
		 */
		public $crons;

		/**
		 * Holds an insteance of the InactiveUsers class.
		 *
		 * @var PPMWP\InactiveUsers
		 */
		public $inactive;

		/**
		 * Instantiate
		 */
		private function __construct() {

			new PPM_User_Meta_Upgrade_Process();
			new PPM_Apply_Timestamp_For_Users_Process();
			new PPM_Reset_User_PW_Process();

			$this->register_dependencies();

			$can_continue = true;


			// Check if a user is on a trial or has an activated license that enables premium features.
			if ( $can_continue ) {
				// initialise options.
				$this->options = new PPM_WP_Options();
				// initialise rule regexes.
				$this->regex = new PPM_WP_Regex();
				// initialise strings.
				$this->msgs = new PPM_WP_Msgs();

				// Load plugin's text language files.
				add_action( 'init', array( $this, 'localise' ) );
				// Init.
				add_action( 'init', array( $this, 'init' ) );
				// Admin init.
				add_action( 'admin_init', array( $this, 'ppm_overwrite_admin_menu' ) );

				/*
				*/
				// Admin footer.
				add_action( 'admin_footer', array( $this, 'ppm_freemium_submenu' ) );
				// FS pricing url filter.
				add_filter(
					'fs_pricing_url_password-policy-manager',
					function() {
						return esc_url( 'https://www.wpwhitesecurity.com/wordpress-plugins/password-policy-manager-wordpress/pricing/' );
					}
				);
			}

			/*
			*/
			// bootstraps the inactive users feature of the plugin.
			add_action( 'init', array( $this, 'setup_inactive_users_feature' ) );

			// Ensure user is sent to reset if needed.
			add_action( 'admin_init', array( $this, 'redirect_user_to_forced_pw_reset' ) );

			// Update user's last activity.
			add_action( 'wp_login', array( $this, 'update_user_last_activity' ) );
			add_action( 'wp_logout', array( $this, 'update_user_last_activity' ) );
			add_action( 'wp_login_failed', array( $this, 'update_user_last_activity' ) );
			add_action( 'wp_loaded', array( $this, 'register_summary_email_cron' ) );
		}


		/**
		 * Registers some dependency classes and files for the plugin.
		 *
		 * @method register_dependencies
		 * @since  2.1.0
		 */
		public function register_dependencies() {
			require_once PPM_WP_PATH . 'app/crons/class-croninterface.php';
			require_once PPM_WP_PATH . 'app/ajax/class-ajaxinterface.php';
			require_once PPM_WP_PATH . 'app/helpers/class-optionshelper.php';
			$this->hooks();
		}

		/**
		 * Register the inactive users check crons.
		 *
		 * @method register_cron
		 * @since
		 * @return void
		 */
		public function register_summary_email_cron() {
			require_once PPM_WP_PATH . 'app/crons/class-summaryemail.php';
			// setup the cron for this.
			$this->crons['summary_email'] = new PPMWP\Crons\SummaryEmail( $this );
			$this->crons['summary_email']->register();
		}

		/**
		 * Adds various hooks that are used for the plugin.
		 *
		 * @method hooks
		 * @since  2.1.0
		 */
		public function hooks() {
			// filters allowed special characters, this is run with a late
			// priority so that users can add new characters.
			add_filter( 'ppwmp_filter_allowed_special_chars', array( $this, 'remove_excluded_special_chars_from_allowed' ), 15, 1 );

			$this->history = new PPM_WP_History();
			add_action( 'user_register', array( $this->history, 'user_register' ) );
			add_action( 'ppmwp_apply_forced_reset_usermeta', array( $this->history, 'ppm_apply_forced_reset_usermeta' ) );

			$this->new_user = new PPM_New_User_Register();
			add_action( 'wp_login', array( $this->new_user, 'ppm_first_time_login' ), 10, 2 );
		}

		/**
		 * Get a list of all default supported special characters.
		 *
		 * @since  2.1.0
		 * @return string
		 */
		public function get_special_chars() {
			return '[!@#$%^&*()_?£"-+=~;:€<>]';
		}

		/**
		 * Gets the list of allowed special characters passed through a filter
		 * to remove any characters that are dissallowed via options.
		 *
		 * @method get_allowed_special_chars
		 * @since  2.1.0
		 * @return string
		 */
		public function get_allowed_special_chars() {
			// get list of removed characters from option.
			$allowed_chars = $this->get_special_chars();
			// run characters string through filter where chars can be added/removed.
			$special_chars_string = apply_filters( 'ppwmp_filter_allowed_special_chars', $allowed_chars );
			return $special_chars_string;
		}

		/**
		 * Filter that removes special characters from the allowed list.
		 *
		 * @since  2.1.0
		 * @param  string $chars of allowed characters.
		 * @return string
		 */
		public function remove_excluded_special_chars_from_allowed( $chars ) {
			// get disallowed characters from options. First check user options,
			// then global options and fallback to default.
			$remove_chars = ( isset( $this->options->users_options->rules['exclude_special_chars'] ) && isset( $this->options->users_options->excluded_special_chars ) ) ? $this->options->users_options->excluded_special_chars : '';
			// split the remove string into an array of individual characters.

			if ( $remove_chars ) {
				// Decode $remove_chars so we are stripping out the things we need,
				// not looping through the HTML entity chars.
				$remove_chars_array = str_split( html_entity_decode( $remove_chars ) );
				foreach ( $remove_chars_array as $char ) {
					// remove any chars from the allowed list.
					$chars = str_replace( $char, '', $chars );
				}
			}

			// return a maybe updated list of special chars.
			return $chars;
		}


		/**
		 * Overwrite admin menu URL.
		 */
		public function ppm_overwrite_admin_menu() {
			global $submenu;
			if ( isset( $submenu['ppm_wp_settings'] ) ) {
				$menu_index = array_search( 'ppm_wp_settings-pricing', array_column( $submenu['ppm_wp_settings'], 2 ) );
				if ( $menu_index ) {
					$upgrade_menu                              = $submenu['ppm_wp_settings'][ $menu_index ];
					$submenu['ppm_wp_settings'][ $menu_index ] = array_replace( // phpcs:ignore
						$upgrade_menu,
						array_fill_keys(
							array_keys( $upgrade_menu, 'ppm_wp_settings-pricing' ),
							esc_url( 'https://www.wpwhitesecurity.com/wordpress-plugins/password-policy-manager-wordpress/pricing/' )
						)
					);
				}

				$help = array_search( 'Help & Contact Us', array_column( $submenu['ppm_wp_settings'], 0 ) );

				/**
				 * Help menu move to last.
				 *
				 * @var $submenu
				 */
				if ( $help ) {
					$help_menu = $submenu['ppm_wp_settings'][ $help ];
					unset( $submenu['ppm_wp_settings'][ $help ] );
					$submenu['ppm_wp_settings'][] = $help_menu; // phpcs:ignore
				}
			}
		}

		/**
		 * Initialise
		 */
		public function init() {

			$ppm = ppm_wp();

			$this->options->init();

			$user_settings = $ppm->options->users_options;

			$role_setting = $ppm->options->setting_options;

			if ( null != $user_settings ) {
				$this->msgs->init();
			}

			$this->regex->init();
			// Call password history class.
			$history = new PPM_WP_History();
			$history->ppm_after_password_reset();

			// Call password expire class.
			$expire = new PPM_WP_Expire();
			$expire->ppm_authenticate_user();

			// Check change initial password setting is enabled OR not.
			$new_user = new PPM_New_User_Register();
			$new_user->init();

			$new_user = new PPM_User_Profile();
			$new_user->init();

			$shortcodes = new PPM_Shortcodes();
			$shortcodes->init();


			// call ppm history all hook.
			$history->hook();

			$options_master_switch    = PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->master_switch );
			$settings_master_switch   = PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->master_switch );
			$inherit_policies_setting = PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			// Enable all features only if policy switch is enabled.
			if ( $is_needed ) {

				if ( ! PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->enforce_password ) ) {

					$pwd_check = new PPM_WP_Password_Check();

					$pwd_check->hook();

					$pwd_gen = new PPM_WP_Password_Gen();

					$pwd_gen->hook();

					$forms = new PPM_WP_Forms();

					$forms->hook();

					// call ppm expire all hook.
					$expire->hook();

					$reset = new PPM_WP_Reset();

					$reset->hook();
				}
			}

			if ( ! is_multisite() ) {
				$admin = new PPM_WP_Admin( $this->options, $user_settings, $role_setting );
			} else {
				$admin = new PPM_WP_MS_Admin( $this->options, $user_settings, $role_setting );
			}
		}

		/**
		 * Standard singleton pattern.
		 *
		 * @return PPM_WP Returns the current plugin instance.
		 */
		public static function _instance() {
			if ( is_null( self::$_instance ) || ! ( self::$_instance instanceof self ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}


		/**
		 * Checks if a user is exempted from the policies.
		 *
		 * @param integer $user_id User ID.
		 * @return boolean
		 */
		public static function is_user_exempted( $user_id = false ) {

			$ppm = ppm_wp();

			// if no user is supplied, assume they are not exempted.
			if ( false === $user_id ) {
				return false;
			}

			if ( isset( $ppm->options->ppm_setting->exempted['users'] ) && ! empty( $ppm->options->ppm_setting->exempted['users'] ) ) {

				// check if this particular user is exempted.
				if ( in_array( $user_id, $ppm->options->ppm_setting->exempted['users'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Load plugin textdomain.
		 */
		public function localise() {
			load_plugin_textdomain( 'ppm-wp', false, dirname( PPM_WP_BASENAME ) . '/languages/' );
		}

		/**
		 * Create activation timestamp
		 */
		public static function activation_timestamp() {
			update_site_option( PPMWP_PREFIX . '_activation', current_time( 'timestamp' ) );
			self::ppm_multisite_install_plugin();
			self::ppm_apply_timestammp_for_users();
			self::ppm_run_prefix_update();
		}

		/**
		 * Deactivate plugin.
		 */
		public static function ppm_deactivation() {
			// Code here.
		}

		/**
		 * Clean up data
		 */
		public static function cleanup() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			if ( $ppm_setting ) {
				$clear_up_needed = isset( $ppm_setting['clear_history'] ) && ( 'yes' === $ppm_setting['clear_history'] || 1 === $ppm_setting['clear_history'] );

				if ( $clear_up_needed ) {
					self::clear_options();
					self::clear_history();
					self::clear_usermeta();
				}
			}
		}

		/**
		 * Delete both options
		 */
		public static function clear_options() {
			global $wp_roles;
			$roles = $wp_roles->get_names();

			// delete all role options.
			foreach ( $roles as $key => $role ) {
				delete_site_option( PPMWP_PREFIX . '_' . $key . '_options' );
			}

			delete_site_option( PPMWP_PREFIX . '_options' );
			delete_site_option( PPMWP_PREFIX . '_setting' );
			delete_site_option( PPMWP_PREFIX . '_activation' );
			delete_site_option( PPMWP_PREFIX . '_reset_timestamp' );
			delete_site_option( 'ppmwp_plugin_version' );
			delete_site_option( 'ppmwp_prefixes_updated' );
			delete_site_option( 'ppmwp_inactive_users' );
		}

		/**
		 * Clear history
		 */
		public static function clear_history() {
			$args = array(
				'fields' => array( 'ID' ),
			);

			if ( ! is_multisite() ) {
				self::clear_user_history( $args );
			} else {
				self::clear_ms_history( $args );
			}

		}

		/**
		 * Clear User meta
		 */
		public static function clear_usermeta() {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", array( 'ppmwp_%' ) ) );
		}

		/**
		 * Clear History in multisite.
		 *
		 * @param string|array $args History clear arguments.
		 */
		public static function clear_ms_history( $args ) {
			// Specify a large number so we get more than 100 sites.
			$sites_args = array(
				'number' => 10000,
			);
			$sites      = get_sites( $sites_args );

			foreach ( $sites as $site ) {

				switch_to_blog( $site->blog_id );

				$args['blog_id'] = $site->blog_id;

				self::clear_user_history( $args );

				restore_current_blog();
			}
		}

		/**
		 * Clear user history for one site
		 *
		 * @param array $args User query.
		 */
		public static function clear_user_history( $args ) {

			$users = get_users( $args );

			foreach ( $users as $user ) {
				delete_user_meta( $user->ID, PPM_WP_META_KEY );
			}
		}

		/**
		 * Destroy user session.
		 *
		 * @param  int $user_id User ID.
		 */
		public function ppm_user_session_destroy( $user_id ) {
			// get all sessions for user with ID $user_id.
			$sessions = WP_Session_Tokens::get_instance( $user_id );
			// we have got the sessions, destroy them all!
			$sessions->destroy_all();
		}

		/**
		 * Get user by blog ID.
		 *
		 * @param  integer $blog_id     WordPress site ID.
		 * @param  array   $extra_query User query.
		 * @return object|array
		 */
		public function ppm_mu_user_by_blog_id( $blog_id = 0, $extra_query = array() ) {
			// Default query for get blog users.
			$user_query = array(
				'blog_id' => $blog_id,
			);
			// Merge custom query.
			$user_query = array_merge( $user_query, $extra_query );
			// Return user object.
			return get_users( $user_query );
		}

		/**
		 * Get user blog by user ID.
		 *
		 * @param  integer $user_id The id of user to work with.
		 * @return Object|bool Defalut 0
		 */
		public function ppm_mu_get_blog_by_user_id( $user_id = 0 ) {
			$blog_info = get_active_blog_for_user( $user_id );
			// If check user blog object.
			if ( $blog_info ) {
				return (int) $blog_info->blog_id;
			}
			return 0;
		}

		/**
		 * Multisite installation.
		 */
		public static function ppm_multisite_install_plugin() {
			$installation_errors = false;
			// If check multisite and network admin.
			if ( is_multisite() && is_super_admin() && ! is_network_admin() ) {
				$installation_errors  = esc_html__( 'The WPassword plugin is a multisite network tool. Please activate it from the network dashboard.', 'ppm-wp' );
				$installation_errors .= '<br />';
				$installation_errors .= '<a href="javascript:;" onclick="window.top.location.href=\'' . esc_url( network_admin_url( 'plugins.php' ) ) . '\'">' . esc_html__( 'Redirect me to the network dashboard', 'ppm-wp' ) . '</a> ';
			}
			if ( $installation_errors ) {
				?>
				 <html>
				 <head><style>body{margin:0;}.warn-icon-tri{top:7px;left:5px;position:absolute;border-left:16px solid #FFF;border-right:16px solid #FFF;border-bottom:28px solid #C33;height:3px;width:4px}.warn-icon-chr{top:10px;left:18px;position:absolute;color:#FFF;font:26px Georgia}.warn-icon-cir{top:4px;left:0;position:absolute;overflow:hidden;border:6px solid #FFF;border-radius:32px;width:34px;height:34px}.warn-wrap{position:relative;font-size:13px;font-family:sans-serif;padding:6px 48px;line-height:1.4;}</style></head>
 				<body><div class="warn-wrap"><div class="warn-icon-tri"></div><div class="warn-icon-chr">!</div><div class="warn-icon-cir"></div><span><?php echo $installation_errors; // @codingStandardsIgnoreLine ?></span></div></body>
				 </html>
				<?php
				die( 1 );
			}
		}

		/**
		 * Updater for change of prefix.
		 *
		 * @return void
		 */
		public static function ppm_run_prefix_update() {

			// Update plugin version stored in db.
			$plugin_data = get_plugin_data( PPM_WP_FILE, false );
			$plugin_ver  = get_site_option( 'ppmwp_plugin_version' );

			if ( empty( $plugin_ver ) ) {
				update_site_option( 'ppmwp_plugin_version', $plugin_data['Version'] );
			} else {
				return;
			}

			// Check if we have already run.
			$been_updated = get_site_option( 'ppmwp_prefixes_updated' );

			if ( $been_updated ) {
				return;
			}

			// Grab old settings.
			$activation      = get_site_option( '_ppm-wp_activation' );
			$setting         = get_site_option( '_ppm-wp-setting' );
			$options         = get_site_option( '_ppm-wp-options' );
			$reset_timestamp = get_site_option( '_ppm-wp-reset_timestamp' );

			// If nothing is found, its not needed to continue.
			if ( empty( $options ) ) {
				return;
			}

			// Move them to new key.
			$move_activation      = update_site_option( 'ppmwp_activation', current_time( 'timestamp' ) );
			$move_setting         = update_site_option( 'ppmwp_setting', $setting );
			$move_options         = update_site_option( 'ppmwp_options', $options );
			$move_reset_timestamp = update_site_option( 'ppmwp_reset_timestamp', $reset_timestamp );

			// Clear old settings.
			if ( $move_activation ) {
				delete_site_option( '_ppm-wp_activation' );
			}
			if ( $move_setting ) {
				delete_site_option( '_ppm-wp-setting' );
			}
			if ( $options ) {
				delete_site_option( '_ppm-wp-options' );
			}
			if ( $move_reset_timestamp ) {
				delete_site_option( '_ppm-wp-reset_timestamp' );
			}

			// Now lets handle role specific options.
			$roles_obj         = new WP_Roles();
			$roles_names_array = $roles_obj->get_names();

			foreach ( $roles_names_array as $role ) {
				$role_name          = strtolower( str_replace( ' ', '_', $role ) );
				$role_settings      = get_site_option( ' _ppm-wp-' . $role_name . '-options' );
				$move_role_settings = update_site_option( 'ppmwp_' . $role_name . '_options', $role_settings );
				if ( $move_role_settings ) {
					delete_site_option( ' _ppm-wp-' . $role_name . '-options' );
				}
			}

			// Send users for bg processing later.
			$total_users = count_users();
			$batch_size  = 100;
			$slices      = ceil( $total_users['total_users'] / $batch_size );
			$users       = array();

			for ( $count = 0; $count < $slices; $count++ ) {
				$args  = array(
					'number'     => $batch_size,
					'offset'     => $count * $batch_size,
					'fields'     => array( 'ID' ),
					'meta_query' => array(
						array(
							'relation' => 'OR',
							array(
								'meta_key'     => '_ppm_wp_password_history',
								'meta_compare' => 'EXISTS',
							),
							array(
								'meta_key'     => '_ppm_wp_delayed_reset',
								'meta_compare' => 'EXISTS',
							),
							array(
								'meta_key'     => '_ppm_wp_password_expired',
								'meta_compare' => 'EXISTS',
							),
							array(
								'meta_key'     => '_ppm_wp_new_user_register',
								'meta_compare' => 'EXISTS',
							),
							array(
								'meta_key'     => '_ppm_wp_reset_pw_on_login',
								'meta_compare' => 'EXISTS',
							),
							array(
								'meta_key'     => '_ppm_wp_dormant_user_flag',
								'meta_compare' => 'EXISTS',
							),
						),
					),
				);
				$users = get_users( $args );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user_id ) {
						$background_process = new PPM_User_Meta_Upgrade_Process();
						$background_process->push_to_queue( $user_id );
					}
				}

				$background_process->save();
			}

			// Fire off bg processes.
			$background_process->dispatch();
		}

		/**
		 * Applies activation timestamp to user meta.
		 *
		 * @return void
		 */
		public static function ppm_apply_timestammp_for_users() {

			// Send users for bg processing later.
			$total_users = count_users();
			$batch_size  = 100;
			$slices      = ceil( $total_users['total_users'] / $batch_size );
			$users       = array();

			for ( $count = 0; $count < $slices; $count++ ) {
				$args  = array(
					'number' => $batch_size,
					'offset' => $count * $batch_size,
					'fields' => array( 'ID' ),
				);
				$users = get_users( $args );

				if ( ! empty( $users ) ) {
					$background_process = new PPM_Apply_Timestamp_For_Users_Process();
					$background_process->push_to_queue( $users );
				}

				$background_process->save()->dispatch();
			}

		}

		/**
		 * Return users who are required to reset their password, back to the reset form.
		 */
		public function redirect_user_to_forced_pw_reset() {
			/* Ensure we dont redirect ajax requests for generating passwords. */
			if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'generate-password' === $_REQUEST['action'] ) {
				return;
			}

			$user = wp_get_current_user();

			// Get user reset key.
			$reset            = new PPM_WP_Reset();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'reset-on-login' );

			// If check reset key exists OR not.
			if ( $verify_reset_key ) {
				$this->handle_user_redirection( $verify_reset_key );
			}
		}

		/**
		 * Simple handler to perform redirection where needed.
		 *
		 * @param Object  $verify_reset_key - Users reset key.
		 * @param boolean $send_json_after - Send json when done.
		 * @param boolean $exit_on_over - Exit or die.
		 * @return void
		 */
		public function handle_user_redirection( $verify_reset_key, $send_json_after = false, $exit_on_over = false ) {

			if ( $verify_reset_key ) {
				$redirect_to = add_query_arg(
					array(
						'action' => 'rp',
						'key'    => $verify_reset_key->reset_key,
						'login'  => rawurlencode( $verify_reset_key->user_login ),
					),
					network_site_url( 'wp-login.php' )
				);
				if ( $send_json_after ) {
					wp_send_json_success(
						array(
							'success'  => true,
							'redirect' => $redirect_to,
						)
					);
				} else {
					wp_safe_redirect( $redirect_to );
					if ( $exit_on_over ) {
						exit;
					} else {
						die;
					}
				}
			}

		}

		/**
		 * Update the users last activity
		 *
		 * @param  int|string $user - User for which to update.
		 * @return void
		 */
		public function update_user_last_activity( $user ) {

			if ( is_int( $user ) ) {
				$user = get_user_by( 'id', $user );
			} elseif ( is_string( $user ) ) {
				// If user is using an email, act accordingly.
				if ( filter_var( $user, FILTER_VALIDATE_EMAIL ) ) {
					$user = get_user_by( 'email', $user );
				} else {
					$user = get_user_by( 'login', $user );
				}
			} else {
				$user = wp_get_current_user();
			}

			if ( isset( $user->ID ) ) {
				// Apply last active time.
				update_user_meta( $user->ID, 'ppmwp_last_activity', current_time( 'timestamp' ) );
			}
		}

		/**
		 * Generates system info panel.
		 */
		public function get_sysinfo() {
			// System info.
			global $wpdb;

			$sysinfo = '### System Info → Begin ###' . "\n\n";

			// Start with the basics...
			$sysinfo .= '-- Site Info --' . "\n\n";
			$sysinfo .= 'Site URL (WP Address):    ' . site_url() . "\n";
			$sysinfo .= 'Home URL (Site Address):  ' . home_url() . "\n";
			$sysinfo .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

			// Get theme info.
			$theme_data   = wp_get_theme();
			$theme        = $theme_data->Name . ' ' . $theme_data->Version; // phpcs:ignore
			$parent_theme = $theme_data->Template; // phpcs:ignore
			if ( ! empty( $parent_theme ) ) {
				$parent_theme_data = wp_get_theme( $parent_theme );
				$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version; // phpcs:ignore
			}

			// Language information.
			$locale = get_locale();

			// WordPress configuration.
			$sysinfo .= "\n" . '-- WordPress Configuration --' . "\n\n";
			$sysinfo .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
			$sysinfo .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
			$sysinfo .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
			$sysinfo .= 'Active Theme:             ' . $theme . "\n";
			if ( $parent_theme !== $theme ) {
				$sysinfo .= 'Parent Theme:             ' . $parent_theme . "\n";
			}
			$sysinfo .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

			// Only show page specs if frontpage is set to 'page'.
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$front_page_id = (int) get_option( 'page_on_front' );
				$blog_page_id  = (int) get_option( 'page_for_posts' );

				$sysinfo .= 'Page On Front:            ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
				$sysinfo .= 'Page For Posts:           ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
			}

			$sysinfo .= 'ABSPATH:                  ' . ABSPATH . "\n";
			$sysinfo .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$sysinfo .= 'WP Memory Limit:          ' . WP_MEMORY_LIMIT . "\n";

			// Get plugins that have an update.
			$updates = get_plugin_updates();

			// Must-use plugins.
			// NOTE: MU plugins can't show updates!
			$muplugins = get_mu_plugins();
			if ( count( $muplugins ) > 0 ) {
				$sysinfo .= "\n" . '-- Must-Use Plugins --' . "\n\n";

				foreach ( $muplugins as $plugin => $plugin_data ) {
					$sysinfo .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
				}
			}

			// WordPress active plugins.
			$sysinfo .= "\n" . '-- WordPress Active Plugins --' . "\n\n";

			$plugins        = get_plugins();
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( ! in_array( $plugin_path, $active_plugins ) ) {
					continue;
				}

				$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			// WordPress inactive plugins.
			$sysinfo .= "\n" . '-- WordPress Inactive Plugins --' . "\n\n";

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( in_array( $plugin_path, $active_plugins ) ) {
					continue;
				}

				$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			if ( is_multisite() ) {
				// WordPress Multisite active plugins.
				$sysinfo .= "\n" . '-- Network Active Plugins --' . "\n\n";

				$plugins        = wp_get_active_network_plugins();
				$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

				foreach ( $plugins as $plugin_path ) {
					$plugin_base = plugin_basename( $plugin_path );

					if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
						continue;
					}

					$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
					$plugin   = get_plugin_data( $plugin_path );
					$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
				}
			}

			// Server configuration.
			$server_software = filter_input( INPUT_SERVER, 'SERVER_SOFTWARE', FILTER_SANITIZE_STRING );
			$sysinfo        .= "\n" . '-- Webserver Configuration --' . "\n\n";
			$sysinfo        .= 'PHP Version:              ' . PHP_VERSION . "\n";
			$sysinfo        .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";

			if ( isset( $server_software ) ) {
				$sysinfo .= 'Webserver Info:           ' . $server_software . "\n";
			} else {
				$sysinfo .= 'Webserver Info:           Global $_SERVER array is not set.' . "\n";
			}

			// PHP configs.
			$sysinfo .= "\n" . '-- PHP Configuration --' . "\n\n";
			$sysinfo .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
			$sysinfo .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
			$sysinfo .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
			$sysinfo .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
			$sysinfo .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
			$sysinfo .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
			$sysinfo .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

			$sysinfo .= "\n" . '-- PPMWP Settings  --' . "\n\n";

			$ppm_options = $this->options->ppm_setting;

			if ( ! empty( $ppm_options ) ) {
				foreach ( $ppm_options as $option => $value ) {
					$sysinfo .= 'Option: ' . $option . "\n";
					$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n";
				}
			}

			$sysinfo .= "\n" . '-- PPMWP Role Options  --' . "\n\n";

			$roles_obj = wp_roles();

			foreach ( $roles_obj->role_names as $role ) {
				$role_options = PPMWP\Helpers\OptionsHelper::get_role_options( $role );
				$sysinfo     .= "\n" . '-- ' . $role . '  --' . "\n\n";
				if ( ! empty( $role_options ) ) {
					foreach ( $role_options as $option => $value ) {
						$sysinfo .= 'Option: ' . $option . "\n";
						$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n";
					}
				}
			}

			$sysinfo .= "\n" . '### System Info → End ###' . "\n\n";

			return $sysinfo;
		}

	}
}
