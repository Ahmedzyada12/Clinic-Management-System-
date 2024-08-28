<?php

return [
    /**
     * API Token Key (string)
     * Accepted value:
     * Live Token: https://myfatoorah.readme.io/docs/live-token
     * Test Token: https://myfatoorah.readme.io/docs/test-token
     */
    // 'api_key' => ' rLtt6JWvbUHDDhsZnfpAhpYk4dxYDQkbcPTyGaKp2TYqQgG7FGZ5Th_WD53Oq8Ebz6A53njUoo1w3pjU1D4vs_ZMqFiz_j0urb_BH9Oq9VZoKFoJEDAbRZepGcQanImyYrry7Kt6MnMdgfG5jn4HngWoRdKduNNyP4kzcp3mRv7x00ahkm9LAK7ZRieg7k1PDAnBIOG3EyVSJ5kK4WLMvYr7sCwHbHcu4A5WwelxYK0GMJy37bNAarSJDFQsJ2ZvJjvMDmfWwDVFEVe_5tOomfVNt6bOg9mexbGjMrnHBnKnZR1vQbBtQieDlQepzTZMuQrSuKn-t5XZM7V6fCW7oP-uXGX-sMOajeX65JOf6XVpk29DP6ro8WTAflCDANC193yof8-f5_EYY-3hXhJj7RBXmizDpneEQDSaSz5sFk0sV5qPcARJ9zGG73vuGFyenjPPmtDtXtpx35A-BVcOSBYVIWe9kndG3nclfefjKEuZ3m4jL9Gg1h2JBvmXSMYiZtp9MR5I6pvbvylU_PP5xJFSjVTIz7IQSjcVGO41npnwIxRXNRxFOdIUHn0tjQ-7LwvEcTXyPsHXcMD8WtgBh-wxR8aKX7WPSsT1O8d8reb2aR7K3rkV3K82K_0OgawImEpwSvp9MNKynEAJQS6ZHe_J_l77652xwPNxMRTMASk1ZsJL',

    // 'api_key' => '',

    // 'api_key' => 'PRw9uTPb8yNNG47Ikr3ukSN51J_-gUMDDvjbjAm6C_kGqriUjgBSdY7AzfP2HspvvBnHBhyTJzba1qu6sj21-48fPIAgksx55oZdxt10LoHzzw_Zuh3Ec19u8HWV2qtulCB0pTayXpiacMyTVKqMe3bZ73uSbRIEbJVdwzdCC9AqJKY3CKL3yhNJsng_wLTQ8yf5l7oJrUvY4NbxSrs2WP2GGi7AvUkmjYAY9_sCddhrIKZaLYDczYAzTH1MV87tNaimeHgoE_oWsu7TsfjU7suyxtTbbFwWHrFKf-wM31OfAkdEHZW18rzC6dYG6UbW2VR0Kmy7SWX7Fv7M9KGQ2Mh0NKSP3_m5AFiVKKle5BoixPVB0C20P-38oK6mGfmD50sd2wpiG1NeI7fn5pDsUlft-WhC1hMDD3M7jlFWJ9PpNdz39h2mGdY3SprF_W7PjzvJ3QfGSTg7_m--qi29mE9MkJQ2Fy43VUUIj3p9s1KfzA-YAdYpiT2W7V1u6Z9l_xdVzQDv1s3mAZHfyirwJcRgP_6DOKe2zvhWio1aDQdKKOsrCu-b5FD5jxQeqX8hURY3TppoX5SQUaaNFW4_skkbIXwkbn3sAkiQZ78MMiVxIQaYQN8ENa09BXj5T1kZ8GRuweR1eiLXzTCAvS9aiJLB_Ji-kLcM341nE_gsuf8ZPr3MzuAZZPH7ZPcoqxweRL9V8_alyOwmeO7OB5-1F2cn0_guWi04VptDgrGG5JjZN8PU',

    /**
     * Test Mode (boolean)
     * Accepted value: true for the test mode or false for the live mode
     */
    'test_mode' => true,
    /**
     * Country ISO Code (string)
     * Accepted value: KWT, SAU, ARE, QAT, BHR, OMN, JOD, or EGY.
     */
    'country_iso' => 'EGY',
    'vcCode' => 'EGY',
    /**
     * Save card (boolean)
     * Accepted value: true if you want to enable save card options.
     * You should contact your account manager to enable this feature in your MyFatoorah account as well.
     */
    'save_card' => true,
    /**
     * Webhook secret key (string)
     * Enable webhook on your MyFatoorah account setting then paste the secret key here.
     * The webhook link is: https://{example.com}/myfatoorah/webhook
     */
    'webhook_secret_key' => '',
    /**
     * Register Apple Pay (boolean)
     * Set it to true to show the Apple Pay on the checkout page.
     * First, verify your domain with Apple Pay before you set it to true.
     * You can either follow the steps here: https://docs.myfatoorah.com/docs/apple-pay#verify-your-domain-with-apple-pay or contact the MyFatoorah support team (tech@myfatoorah.com).
     */
    'register_apple_pay' => false
];