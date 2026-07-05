package com.photonbounce.leads.ads

import android.content.Context
import com.google.android.gms.ads.*
import com.google.android.gms.ads.interstitial.InterstitialAd
import com.google.android.gms.ads.interstitial.InterstitialAdLoadCallback

class AdManager(private val context: Context) {

    private var interstitialAd: InterstitialAd? = null
    private var leadViewCount = 0

    companion object {
        const val BANNER_AD_UNIT_ID = "ca-app-pub-XXXXXXXXXXXXXXXX/XXXXXXXXXX"
        const val INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-XXXXXXXXXXXXXXXX/XXXXXXXXXX"
        const val INTERSTITIAL_INTERVAL = 5
    }

    fun loadInterstitialAd() {
        val adRequest = AdRequest.Builder().build()
        InterstitialAd.load(
            context,
            INTERSTITIAL_AD_UNIT_ID,
            adRequest,
            object : InterstitialAdLoadCallback() {
                override fun onAdLoaded(ad: InterstitialAd) {
                    interstitialAd = ad
                }
                override fun onAdFailedToLoad(error: LoadAdError) {
                    interstitialAd = null
                }
            }
        )
    }

    fun showInterstitialIfNeeded(activity: android.app.Activity) {
        leadViewCount++
        if (leadViewCount >= INTERSTITIAL_INTERVAL) {
            leadViewCount = 0
            interstitialAd?.show(activity)
            loadInterstitialAd()
        }
    }

    fun getBannerAdRequest(): AdRequest = AdRequest.Builder().build()

    fun resetLeadViewCount() {
        leadViewCount = 0
    }
}
