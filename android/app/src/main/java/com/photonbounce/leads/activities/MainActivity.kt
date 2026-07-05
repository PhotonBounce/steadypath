package com.photonbounce.leads.activities

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.ViewModelProvider
import androidx.navigation.fragment.NavHostFragment
import androidx.navigation.ui.setupWithNavController
import com.google.android.gms.ads.AdRequest
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.R
import com.photonbounce.leads.ads.AdManager
import com.photonbounce.leads.databinding.ActivityMainBinding
import com.photonbounce.leads.utils.TokenManager
import com.photonbounce.leads.viewmodels.ViewModelFactory

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var tokenManager: TokenManager
    private lateinit var adManager: AdManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val app = application as LeadsApplication
        tokenManager = app.tokenManager

        // Check auth
        if (!tokenManager.isLoggedIn()) {
            startActivity(Intent(this, AuthActivity::class.java))
            finish()
            return
        }

        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val navHostFragment = supportFragmentManager
            .findFragmentById(R.id.nav_host_fragment) as NavHostFragment
        val navController = navHostFragment.navController
        binding.bottomNav.setupWithNavController(navController)

        // Setup ads for free users
        adManager = AdManager(this)
        if (tokenManager.getUserTier() != "vip") {
            setupBannerAd()
            adManager.loadInterstitialAd()
        } else {
            binding.adView.visibility = android.view.View.GONE
        }
    }

    private fun setupBannerAd() {
        val adRequest = adManager.getBannerAdRequest()
        binding.adView.loadAd(adRequest)
    }

    fun showInterstitial() {
        if (tokenManager.getUserTier() != "vip") {
            adManager.showInterstitialIfNeeded(this)
        }
    }
}
