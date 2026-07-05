package com.photonbounce.leads.activities

import android.os.Bundle
import android.view.MenuItem
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.lifecycleScope
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.databinding.ActivityLeadDetailBinding
import com.photonbounce.leads.utils.IntentUtils
import com.photonbounce.leads.viewmodels.LeadsViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory
import kotlinx.coroutines.launch

class LeadDetailActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLeadDetailBinding
    private lateinit var viewModel: LeadsViewModel
    private var leadId: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLeadDetailBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)

        leadId = intent.getStringExtra(EXTRA_LEAD_ID) ?: return finish()

        val app = application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[LeadsViewModel::class.java]

        loadLeadDetails()
    }

    private fun loadLeadDetails() {
        lifecycleScope.launch {
            val lead = viewModel.getLead(leadId) ?: return@launch finish()

            binding.tvLeadName.text = lead.name
            binding.tvLeadEmail.text = lead.email ?: "No email"
            binding.tvLeadPhone.text = lead.phone ?: "No phone"
            binding.tvLeadCompany.text = lead.company ?: "No company"
            binding.tvLeadStatus.text = lead.status.replaceFirstChar { it.uppercase() }
            binding.tvLeadSource.text = "Source: ${lead.source ?: "Unknown"}"
            binding.tvLeadNiche.text = "Niche: ${lead.niche ?: "General"}"

            // ML Score - hidden for free users
            val isVip = (application as LeadsApplication).tokenManager.getUserTier() == "vip"
            if (isVip && lead.mlScore != null) {
                binding.tvMlScore.text = "ML Score: ${(lead.mlScore * 100).toInt()}%"
                binding.tvMlScore.visibility = android.view.View.VISIBLE
            } else {
                binding.tvMlScore.visibility = android.view.View.GONE
            }

            // Notes
            binding.tvNotes.text = lead.notes ?: "No notes"

            // Action buttons
            binding.btnCall.setOnClickListener {
                lead.phone?.let { phone -> IntentUtils.dialPhone(this@LeadDetailActivity, phone) }
            }
            binding.btnEmail.setOnClickListener {
                lead.email?.let { email -> IntentUtils.sendEmail(this@LeadDetailActivity, email) }
            }

            // Status changer
            binding.spinnerStatus.setSelection(getStatusIndex(lead.status))
            binding.btnUpdateStatus.setOnClickListener {
                val newStatus = getStatusFromIndex(binding.spinnerStatus.selectedItemPosition)
                viewModel.updateLeadStatus(leadId, newStatus)
            }

            // Add note
            binding.btnAddNote.setOnClickListener {
                val note = binding.etNote.text.toString()
                if (note.isNotBlank()) {
                    viewModel.updateLeadStatus(leadId, note) // TODO: separate note update
                    binding.etNote.text?.clear()
                }
            }
        }
    }

    private fun getStatusIndex(status: String): Int {
        return listOf("new", "contacted", "qualified", "proposal", "won", "lost", "archived")
            .indexOf(status).coerceAtLeast(0)
    }

    private fun getStatusFromIndex(index: Int): String {
        return listOf("new", "contacted", "qualified", "proposal", "won", "lost", "archived")
            .getOrElse(index) { "new" }
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        if (item.itemId == android.R.id.home) {
            finish()
            return true
        }
        return super.onOptionsItemSelected(item)
    }

    companion object {
        const val EXTRA_LEAD_ID = "lead_id"
    }
}
