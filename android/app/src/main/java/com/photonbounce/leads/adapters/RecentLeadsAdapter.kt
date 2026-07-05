package com.photonbounce.leads.adapters

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.photonbounce.leads.databinding.ItemRecentLeadBinding
import com.photonbounce.leads.db.LeadEntity

class RecentLeadsAdapter(
    private val onClick: (String) -> Unit
) : ListAdapter<LeadEntity, RecentLeadsAdapter.ViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemRecentLeadBinding.inflate(
            LayoutInflater.from(parent.context), parent, false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class ViewHolder(private val binding: ItemRecentLeadBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(lead: LeadEntity) {
            binding.tvName.text = lead.name
            binding.tvStatus.text = lead.status.replaceFirstChar { it.uppercase() }
            binding.tvTime.text = lead.createdAt ?: ""
            binding.root.setOnClickListener { onClick(lead.id) }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<LeadEntity>() {
        override fun areItemsTheSame(old: LeadEntity, new: LeadEntity) = old.id == new.id
        override fun areContentsTheSame(old: LeadEntity, new: LeadEntity) = old == new
    }
}
